function selectModenaBanklink(id, value) {
    unselectAllModenaBanklinks();
    document.getElementById('mdn_bl_option_' + id).classList.add('mdn_checked');
    document.getElementById('modena_selected_payment_method').value = value;
}

function unselectAllModenaBanklinks() {
    var allBanklinks = document.querySelectorAll('[id^="mdn_bl_option_"]');
    for (i = 0; i < allBanklinks.length; i++) {
        unselectModenaBanklink(allBanklinks[i]);
    }
}

function unselectModenaBanklink(banklink) {
    banklink.classList.remove('mdn_checked');
}

function toggleLocality() {
    var selectedLocality = document.getElementById('locality_selector').value;
	document.getElementById('modena_selected_locality').value = selectedLocality;	
	
    var allOptions = document.querySelectorAll('#mdn_banklinks_wrapper li');

    allOptions.forEach(function(option) {
        if (option.classList.contains('locality_' + selectedLocality)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });

    unselectAllModenaBanklinks();
    // Optionally, select the first option of the chosen locality
    var firstOption = document.querySelector('.locality_' + selectedLocality + ' img');
    if (firstOption) {
		var code = 	document.getElementById(firstOption.id).attributes['code'].value;
        selectModenaBanklink(firstOption.id.replace('mdn_bl_option_', ''), code);
	}
}