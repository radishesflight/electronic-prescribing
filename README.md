# electronic-prescribing
电子处方
```php
        $obj=new ElectronicPrescribingServer('https://test-zxcf.gdsz101.com/api','13311111111','test123456');
       $a= $obj->queryRxInfoHisPage();
        $obj->createRxinfo([
            'ywid'=>mt_rand(11111,99999),
            'status'=>1,
            'patient_name'=>'缪文国',
            'patient_age'=>'29',
            'patient_sex'=>'男',
            'patient_phone'=>'13589887777',
            'patient_idcard'=>'530321199409070016',
            'rx_item_json'=>'[{"project_code":"20220804536170","project_name":"鲑鱼降钙素鼻喷剂","standard":"第三⽅规格","total":"1","batchnumber":"批号","manufacturer_code":"⼚家编码","manufacturer_name":"⼚家名称"}]',
            'disease_json'=>'[{"icd_code":"编码","icd_name":"名称"}]',
        ])->createChat([],'你好啊');
        $a=$obj->queryChatLogList();
```
