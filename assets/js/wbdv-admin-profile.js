jQuery(document).ready(function($) {
    $("#wbdv_profile_datepicker").datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
      yearRange: "-100:+0",
      maxDate: 'now'
    }); 
})