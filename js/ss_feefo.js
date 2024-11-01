
jQuery(document).ready(function ($) {

    var dateNow = new Date();
    var currentMonth = dateNow.getMonth();
    var currentDate = dateNow.getDate();
    var currentYear = dateNow.getFullYear();


    var dates = $("#datepicker-field-end, #datepicker-field-start").datepicker({
        defaultDate: "+1w",
        numberOfMonths: 3,
        dateFormat: "yy-mm-dd",
        maxDate: new Date(currentYear, currentMonth, currentDate),
        onSelect: function (selectedDate) {
            var option = this.id == "datepicker-field-start" ? "minDate" : "maxDate",
                    instance = $(this).data("datepicker"),
                    date = $.datepicker.parseDate(
                            instance.settings.dateFormat ||
                            $.datepicker._defaults.dateFormat,
                            selectedDate, instance.settings);
            dates.not(this).datepicker("option", option, date);
        }
    });
});