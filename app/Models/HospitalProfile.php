<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalProfile extends Model
{
    use HasUuids;

    protected $table = 'hospital_profiles';

    protected $fillable = [
        'hospital_contact_id', 'island_id', 'no_of_beds', 'grade', 'population', 'avg_outpatient_per_day',
        'avg_inpatient_per_month',
        'staff_physiotherapy', 'staff_dermatology', 'staff_ortho', 'staff_medicine',
        'staff_surgeon', 'staff_gynaecology', 'staff_paediatrician', 'staff_ent',
        'staff_dental', 'staff_ophthalmology', 'staff_psychology', 'staff_radiology',
        'staff_anesthesiologist', 'staff_medical_officer', 'staff_psychiatrist',
        'nurses_clinical', 'nurses_senior_registered', 'nurses_registered', 'nurses_enrolled',
        'admin_officers_senior', 'admin_officers', 'customer_service', 'drivers', 'lab_tech', 'other_staffs',
        'ambulance_running_condition', 'ambulance_total',
        'lab_service_available', 'poct_available', 'launch_boat_service',
        'operation_theatre_service', 'emergency_room_service', 'radiology_service',
        'public_health_unit_service', 'sterilization_service',
        'medical_consumables_status', 'laboratory_reagents_status',
        'life_saving_drugs_status', 'sto_pharmacy_status',
        'staff_status', 'building_status', 'project_information', 'other_information',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'hospital_contact_id' => 'string',
        'island_id' => 'string',
        'no_of_beds' => 'integer',
        'population' => 'integer',
        'avg_outpatient_per_day' => 'integer',
        'avg_inpatient_per_month' => 'integer',
        'staff_physiotherapy' => 'integer',
        'staff_dermatology' => 'integer',
        'staff_ortho' => 'integer',
        'staff_medicine' => 'integer',
        'staff_surgeon' => 'integer',
        'staff_gynaecology' => 'integer',
        'staff_paediatrician' => 'integer',
        'staff_ent' => 'integer',
        'staff_dental' => 'integer',
        'staff_ophthalmology' => 'integer',
        'staff_psychology' => 'integer',
        'staff_radiology' => 'integer',
        'staff_anesthesiologist' => 'integer',
        'staff_medical_officer' => 'integer',
        'staff_psychiatrist' => 'integer',
        'nurses_clinical' => 'integer',
        'nurses_senior_registered' => 'integer',
        'nurses_registered' => 'integer',
        'nurses_enrolled' => 'integer',
        'admin_officers_senior' => 'integer',
        'admin_officers' => 'integer',
        'customer_service' => 'integer',
        'drivers' => 'integer',
        'lab_tech' => 'integer',
        'other_staffs' => 'integer',
        'ambulance_running_condition' => 'integer',
        'ambulance_total' => 'integer',
        'lab_service_available' => 'boolean',
        'poct_available' => 'boolean',
        'launch_boat_service' => 'boolean',
        'operation_theatre_service' => 'boolean',
        'emergency_room_service' => 'boolean',
        'radiology_service' => 'boolean',
        'public_health_unit_service' => 'boolean',
        'sterilization_service' => 'boolean',
    ];

    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class, 'island_id');
    }

    public function hospitalContact(): BelongsTo
    {
        return $this->belongsTo(HospitalContact::class, 'hospital_contact_id');
    }

    public function getMedicalStaffTotalAttribute(): int
    {
        return $this->staff_physiotherapy + $this->staff_dermatology + $this->staff_ortho
            + $this->staff_medicine + $this->staff_surgeon + $this->staff_gynaecology
            + $this->staff_paediatrician + $this->staff_ent + $this->staff_dental
            + $this->staff_ophthalmology + $this->staff_psychology + $this->staff_radiology
            + $this->staff_anesthesiologist + $this->staff_medical_officer + $this->staff_psychiatrist;
    }

    public function getNursingStaffTotalAttribute(): int
    {
        return $this->nurses_clinical + $this->nurses_senior_registered
            + $this->nurses_registered + $this->nurses_enrolled;
    }

    public function getAdminStaffTotalAttribute(): int
    {
        return $this->admin_officers_senior + $this->admin_officers + $this->customer_service
            + $this->drivers + $this->lab_tech + $this->other_staffs;
    }

    public function getStaffTotalAttribute(): int
    {
        return $this->medical_staff_total + $this->nursing_staff_total + $this->admin_staff_total;
    }

    public function getActiveServicesAttribute(): int
    {
        $services = [
            'lab_service_available', 'poct_available', 'launch_boat_service',
            'operation_theatre_service', 'emergency_room_service', 'radiology_service',
            'public_health_unit_service', 'sterilization_service',
        ];
        $count = 0;
        foreach ($services as $s) {
            if ($this->{$s}) {
                $count++;
            }
        }

        return $count;
    }
}
