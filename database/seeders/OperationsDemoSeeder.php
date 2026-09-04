<?php

namespace Database\Seeders;

use App\Models\EmergencyIncident;
use App\Models\EquipmentFault;
use App\Models\HospitalContact;
use App\Models\HospitalDocument;
use App\Models\HospitalProfile;
use App\Models\Island;
use App\Models\Profile;
use App\Models\Task;
use App\Models\TrackedExpiryItem;
use App\Models\TransportAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Operations demo data is disabled in production.');
            return;
        }

        $admin = Profile::where('email', 'admin@rahs.mv')->first();
        $staff = Profile::where('email', 'staff@rahs.mv')->first();
        $coordinator = Profile::where('email', 'coordinator@rahs.mv')->first();
        $supervisor = Profile::where('email', 'supervisor@rahs.mv')->first();
        $hospitals = [];
        $names = [
            'Male City' => ['Indira Gandhi Memorial Hospital', 'Dr. Aishath Shareefa', '3335335', 'A'],
            'Hulhumale' => ['Hulhumale Hospital', 'Dr. Mohamed Niyaz', '3350037', 'B'],
            'Hithadhoo' => ['Addu Equatorial Hospital', 'Dr. Fathimath Najeeba', '6888868', 'B'],
            'Dhiddhoo' => ['Haa Alif Atoll Hospital', 'Dr. Ahmed Shafeeq', '6500037', 'C'],
        ];

        DB::transaction(function () use (&$hospitals, $names, $admin, $staff, $coordinator, $supervisor) {
            foreach ($names as $islandName => [$hospitalName, $manager, $phone, $grade]) {
                $island = Island::where('name', $islandName)->firstOrFail();
                if ($islandName === 'Male City' && $staff) {
                    $island->update(['assigned_staff_id' => $staff->id]);
                }
                $island->atoll()->update(['coordinator_id' => $coordinator?->id, 'supervisor_id' => $supervisor?->id]);
                $contact = HospitalContact::updateOrCreate(
                    ['hospital_name' => $hospitalName],
                    ['island_id' => $island->id, 'manager_name' => $manager, 'contact_number' => $phone, 'contact_designation' => 'Medical Superintendent', 'status' => 'active']
                );
                $profile = HospitalProfile::updateOrCreate(
                    ['hospital_contact_id' => $contact->id],
                    ['island_id' => $island->id, 'grade' => $grade, 'no_of_beds' => $grade === 'A' ? 220 : 45, 'population' => $grade === 'A' ? 130000 : 18000, 'ambulance_total' => 2, 'ambulance_running_condition' => 1, 'lab_service_available' => true, 'emergency_room_service' => true, 'radiology_service' => $grade !== 'C', 'medical_consumables_status' => 'available', 'laboratory_reagents_status' => 'limited', 'life_saving_drugs_status' => 'available', 'staff_status' => 'limited', 'building_status' => 'normal']
                );
                $hospitals[$islandName] = $profile;
            }

            $male = $hospitals['Male City'];
            $hulhumale = $hospitals['Hulhumale'];
            $addu = $hospitals['Hithadhoo'];
            $dhiddhoo = $hospitals['Dhiddhoo'];

            $task = Task::updateOrCreate(
                ['title' => '[EMERGENCY] Oxygen manifold pressure failure'],
                ['creator_description' => 'Central oxygen pressure dropped below the safe operating threshold.', 'status' => 'in_progress', 'priority' => 'urgent', 'assigned_by' => $admin?->id, 'assigned_to' => $staff?->id, 'island_id' => $male->island_id, 'due_date' => today(), 'task_types' => ['Emergency escalation']]
            );
            EmergencyIncident::updateOrCreate(
                ['hospital_profile_id' => $male->id, 'title' => 'Oxygen manifold pressure failure'],
                ['island_id' => $male->island_id, 'task_id' => $task->id, 'description' => 'Engineering has isolated the secondary manifold. Cylinder backup is active while the regulator is replaced.', 'severity' => 'critical', 'status' => 'acknowledged', 'acknowledged_at' => now()->subMinutes(35), 'created_by' => $admin?->id]
            );
            EmergencyIncident::updateOrCreate(
                ['hospital_profile_id' => $addu->id, 'title' => 'Emergency generator instability'],
                ['island_id' => $addu->island_id, 'description' => 'Generator two is producing unstable voltage. Critical areas remain connected to generator one.', 'severity' => 'high', 'status' => 'active', 'created_by' => $staff?->id]
            );

            $faults = [
                [$male, 'CT Scanner', 'RAD-CT-01', 'critical', 'Cooling system alarm prevents scanning.', 'repairing', 'Vendor engineer attending; replacement pump requested.'],
                [$hulhumale, 'Hematology Analyzer', 'LAB-HA-04', 'high', 'Quality control repeatedly outside acceptable range.', 'assessing', 'Calibration and reagent checks underway.'],
                [$dhiddhoo, 'Patient Monitor', 'ER-PM-12', 'medium', 'Intermittent SpO2 module connection.', 'reported', null],
            ];
            foreach ($faults as [$hospital, $name, $tag, $severity, $description, $status, $notes]) {
                EquipmentFault::updateOrCreate(['hospital_profile_id' => $hospital->id, 'asset_tag' => $tag], ['equipment_name' => $name, 'category' => 'Clinical equipment', 'severity' => $severity, 'description' => $description, 'status' => $status, 'repair_notes' => $notes, 'expected_return_date' => today()->addDays(3), 'created_by' => $admin?->id, 'maintenance_history' => [['at' => now()->subDay()->toIso8601String(), 'status' => $status, 'notes' => $notes]]]);
            }

            $fleet = [
                [$male, 'ambulance', 'Ambulance MLE-01', 'MED-1024', 'operational', null],
                [$hulhumale, 'ambulance', 'Ambulance HML-02', 'MED-2088', 'maintenance', 'Scheduled brake and tyre replacement.'],
                [$addu, 'launch', 'Medical Launch Equator', 'MV-HEA-14', 'operational', null],
                [$dhiddhoo, 'launch', 'Northern Response Launch', 'MV-HA-07', 'unavailable', 'Port engine cooling fault.'],
            ];
            foreach ($fleet as [$hospital, $type, $name, $registration, $status, $reason]) {
                TransportAsset::updateOrCreate(['hospital_profile_id' => $hospital->id, 'registration_number' => $registration], ['type' => $type, 'name' => $name, 'status' => $status, 'unavailable_reason' => $reason, 'expected_return_date' => $status === 'operational' ? null : today()->addDays(4), 'last_service_date' => today()->subMonths(5), 'next_service_date' => today()->addMonth(), 'updated_by' => $admin?->id]);
            }

            $expiry = [
                [$male, 'medicine', 'Adrenaline 1mg/ml', 'ADR-26-041', 12, 30, 420],
                [$hulhumale, 'reagent', 'Troponin I reagent kit', 'TNI-8842', 24, 45, 8],
                [$addu, 'licence', 'Radiology operating licence', 'MOH-RAD-118', 55, 90, 1],
                [$dhiddhoo, 'equipment_service', 'Autoclave preventive service', 'AUTO-HA-02', -3, 30, 1],
            ];
            foreach ($expiry as [$hospital, $type, $name, $reference, $days, $warning, $quantity]) {
                TrackedExpiryItem::updateOrCreate(['hospital_profile_id' => $hospital->id, 'reference_number' => $reference], ['item_type' => $type, 'name' => $name, 'expiry_date' => today()->addDays($days), 'warning_days' => $warning, 'quantity' => $quantity, 'status' => 'active', 'notes' => 'Demo operational record for workflow review.', 'created_by' => $admin?->id]);
            }

            foreach ([[$male, 'sop', 'Major Incident Response SOP', '3.2', 'major-incident-response-sop.pdf'], [$hulhumale, 'emergency_plan', 'Hospital Evacuation Plan', '2.0', 'hospital-evacuation-plan.pdf'], [$addu, 'inspection', 'Fire Safety Inspection Report', '2026', 'fire-safety-inspection-report.pdf'], [$dhiddhoo, 'licence', 'Laboratory Service Licence', '2026', 'laboratory-service-licence.pdf']] as [$hospital, $category, $title, $version, $filename]) {
                HospitalDocument::updateOrCreate(['hospital_profile_id' => $hospital->id, 'title' => $title], ['category' => $category, 'version' => $version, 'issue_date' => today()->subMonths(2), 'expiry_date' => today()->addMonths(10), 'file_url' => '/demo-documents/'.$filename, 'notes' => 'Demonstration document entry.', 'uploaded_by' => $admin?->id]);
            }
        });

        $this->command?->info('Operations demo data created: 4 hospitals, emergencies, faults, fleet, compliance records and documents.');
    }
}
