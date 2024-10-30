jQuery(document).ready(function($) {
  if( wbdv.method == 'dropdown' ) {
    $("#wbdv_date_confirm, #wpdv_birthday_field").birthdaypicker({
      futureDates: false,
      maxAge: 100,
    });
  }
  if( wbdv.method == 'datepicker' ) {
    $("#wbdv_date_confirm").pickadate({
      selectYears: 100,
      selectMonths: true,
      today: '',
      max: true,
      formatSubmit: 'yyyy-mm-dd'
    });
  }
  if( wbdv.method == 'jqdatepicker' ) {
    $("#wbdv_jquery_confirm").datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
      yearRange: "-100:+0",
      maxDate: 'now'
    });
  }


   $("#wpdv_birthday_field").pickadate({
      selectYears: 100,
      selectMonths: true,
      today: '',
      max: true,
      formatSubmit: 'yyyy-mm-dd'
    });

   $("#wpdv_birthday_picker").datepicker({
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
      yearRange: "-100:+0",
      maxDate: 'now'
    });
});	
