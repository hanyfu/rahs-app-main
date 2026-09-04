function operationsPage(props = {}) {
    return {
        tab: 'overview', role: props.role || 'staff', canAdmin: !!props.canAdmin, searchQuery: '', hospitalScope: 'all', hospitals: props.hospitals || [], _readiness: props.readiness || [], _incidents: props.incidents || [], _faults: props.faults || [], _transport: props.transport || [], _expiryItems: props.expiryItems || [], _documents: props.documents || [], saving: false, uploading: false,
        get readiness(){return this.visible(this._readiness);},set readiness(value){this._readiness=value||[];}, get incidents(){return this.visible(this._incidents);},set incidents(value){this._incidents=value||[];}, get faults(){return this.visible(this._faults);},set faults(value){this._faults=value||[];}, get transport(){return this.visible(this._transport);},set transport(value){this._transport=value||[];}, get expiryItems(){return this.visible(this._expiryItems);},set expiryItems(value){this._expiryItems=value||[];}, get documents(){return this.visible(this._documents);},set documents(value){this._documents=value||[];},
        incidentForm: {}, faultForm: {}, transportForm: {}, expiryForm: {}, documentForm: {},
        init() { this.resetForms(); const requested=window.location.hash.replace('#',''); const tabs=['overview','emergencies','equipment']; if(tabs.includes(requested))this.tab=requested; else if(requested)history.replaceState(null,'',window.location.pathname); },
        setTab(tab) { this.tab=tab; history.replaceState(null,'',`${window.location.pathname}${window.location.search}#${tab}`); this.$nextTick(()=>window.scrollTo({top:0,behavior:'smooth'})); },
        resetForms() { const h=this.hospitals[0]?.id||''; this.incidentForm={hospital_profile_id:h,title:'',description:'',severity:'high',attachment_url:''}; this.faultForm={hospital_profile_id:h,equipment_name:'',asset_tag:'',category:'',severity:'medium',description:'',photo_url:'',expected_return_date:''}; this.transportForm={hospital_profile_id:h,type:'ambulance',name:'',registration_number:'',status:'operational',unavailable_reason:'',expected_return_date:'',last_service_date:'',next_service_date:'',notes:''}; this.expiryForm={hospital_profile_id:h,item_type:'medicine',name:'',reference_number:'',expiry_date:'',warning_days:30,quantity:'',notes:'',document_url:''}; this.documentForm={hospital_profile_id:h,category:'sop',title:'',version:'',issue_date:'',expiry_date:'',file_url:'',notes:''}; },
        hospitalName(item) { return item?.hospital_profile?.hospital_contact?.hospital_name || this.hospitals.find(h=>h.id===item?.hospital_profile_id)?.hospital_contact?.hospital_name || 'Hospital'; },
        visible(collection) { const q=this.searchQuery.trim().toLowerCase(); return collection.filter(item=>{const hospitalId=item.hospital_profile_id||item.hospital_id; if(this.hospitalScope!=='all'&&hospitalId!==this.hospitalScope)return false; if(!q)return true; const searchable=[item.name,item.title,item.description,item.equipment_name,item.reference_number,item.registration_number,item.island,item.atoll,this.hospitalName(item)].filter(Boolean).join(' ').toLowerCase(); return searchable.includes(q);}); },
        visibleReadiness() { return this.visible(this.readiness); },
        label(v) { return String(v||'').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase()); },
        badge(status) { return ['critical','red','unavailable','active','reported'].includes(status)?'bg-red-100 text-red-700':['high','amber','maintenance','assessing','repairing'].includes(status)?'bg-amber-100 text-amber-800':['green','operational','resolved','renewed'].includes(status)?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-700'; },
        date(v) { if(!v)return '—'; return new Date(String(v).slice(0,10)+'T00:00:00').toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'}); },
        expiryState(item) { const days=Math.ceil((new Date(String(item.expiry_date).slice(0,10)+'T00:00:00')-new Date())/86400000); return days<0?`Expired ${Math.abs(days)}d ago`:days===0?'Expires today':`${days} days remaining`; },
        mapPosition(item, index) {
            const names=['haa alif','haa dhaalu','shaviyani','noonu','raa','lhaviyani','baa','kaafu','alif alif','alif dhaal','vaavu','meemu','faafu','dhaalu','thaa','laamu','gaafu alif','gaafu dhaalu','gnaviyani','seenu','addu'];
            const normalized=String(item.atoll||'').toLowerCase();
            let rank=names.findIndex(name=>normalized.includes(name));
            if(rank<0) rank=Math.min(index,names.length-1);
            const top=5+(rank/(names.length-1))*88;
            const left=44+((rank%4)-1.5)*8+((index%3)-1)*2;
            return `top:${top}%;left:${left}%`;
        },
        async refresh() { const d=await window.api.get('/api/operations'); Object.assign(this,d); },
        async save(kind) { const forms={incident:this.incidentForm,fault:this.faultForm,transport:this.transportForm,expiry:this.expiryForm,document:this.documentForm}; const paths={incident:'incidents',fault:'faults',transport:'transport',expiry:'expiry-items',document:'documents'}; try{this.saving=true;await window.api.post(`/api/operations/${paths[kind]}`,forms[kind]);await this.refresh();this.resetForms();Alpine.store('toast').success(`${this.label(kind)} saved`);}catch(e){Alpine.store('toast').error(e.message);}finally{this.saving=false;} },
        async update(path,id,data) { try{await window.api.patch(`/api/operations/${path}/${id}`,data);await this.refresh();Alpine.store('toast').success('Status updated');}catch(e){Alpine.store('toast').error(e.message);} },
        async createActionTask(type,item) { try{const result=await window.api.post('/api/operations/action-task',{type,id:item.id});await this.refresh();Alpine.store('toast').success(result.already_existed?'Existing action task opened':'Action task created');window.location.href=`/tasks?task=${result.task.id}`;}catch(e){Alpine.store('toast').error(e.message);} },
        async removeDocument(item) { if(!await window.confirmAction(`Delete ${item.title}?`,{title:'Delete document',confirmLabel:'Delete'}))return; try{await window.api.del(`/api/operations/documents/${item.id}`);await this.refresh();}catch(e){Alpine.store('toast').error(e.message);} },
        async upload(event, form, key) { const file=event.target.files?.[0]; if(!file)return; try{this.uploading=true;const base64=await new Promise((resolve,reject)=>{const r=new FileReader();r.onload=()=>resolve(String(r.result).split(',')[1]);r.onerror=reject;r.readAsDataURL(file);});const result=await window.api.post('/api/upload',{file:base64,filename:file.name});form[key]=result.url;Alpine.store('toast').success('File uploaded');}catch(e){Alpine.store('toast').error(e.message);}finally{this.uploading=false;event.target.value='';} },
        async enableAlerts() {
            if(!('Notification' in window)||!('serviceWorker' in navigator)||!('PushManager' in window)){Alpine.store('toast').error('Push notifications are not supported on this device');return;}
            try {
                const permission=await Notification.requestPermission();
                if(permission!=='granted') throw new Error('Notification permission was not granted');
                const config=await window.api.get('/api/push-subscriptions/config');
                if(!config.publicKey) throw new Error('Push notifications are not configured on the server');
                const registration=await navigator.serviceWorker.register('/push-sw.js',{scope:'/'});
                await navigator.serviceWorker.ready;
                let subscription=await registration.pushManager.getSubscription();
                if(!subscription) subscription=await registration.pushManager.subscribe({userVisibleOnly:true,applicationServerKey:this.vapidKey(config.publicKey)});
                await window.api.post('/api/push-subscriptions',subscription.toJSON());
                await registration.showNotification('RAHS alerts enabled',{body:'Critical alerts and assigned task updates can now reach this device.',data:{url:'/hospital-operations'}});
                Alpine.store('toast').success('Push alerts enabled on this device');
            } catch(e) { Alpine.store('toast').error(e.message||'Could not enable device alerts'); }
        },
        vapidKey(value) { const padding='='.repeat((4-value.length%4)%4);const raw=atob((value+padding).replace(/-/g,'+').replace(/_/g,'/'));return Uint8Array.from([...raw].map(c=>c.charCodeAt(0))); },
    };
}
window.operationsPage = operationsPage;
