/* ANSI Datepicker Calendar - David Lee 2005

  david [at] davelee [dot] com [dot] au

  project homepage: http://projects.exactlyoneturtle.com/date_picker/

  License:
  use, modify and distribute freely as long as this header remains intact;
  please mail any improvements to the author
*/

function DatePicker(pId, pLang) {
  var version = 0.35;

  /* Configuration options */

  // if false, hide last row if empty
  var constantHeight = true;

  // show select list for year?
  var useDropForYear = false;

  // show select list for month?
  var useDropForMonth = false;

  // number of years before current to show in select list
  var yearsPriorInDrop = 10;

  // number of years after current to show in select list
  var yearsNextInDrop = 10;

  // The current year
  var year = new Date().getFullYear();

  // The first day of the week (0=Sunday, 1=Monday, ...)
  var firstDayOfWeek = 0;

  // show only 3 chars for month in link
  var abbreviateMonthInLink = false;

  // show only 2 chars for year in link
  var abbreviateYearInLink = false;

  // eg 1st
  var showDaySuffixInLink = false;

  // eg 1st; doesn't play nice w/ month selector
  var showDaySuffixInCalendar = false;

  // px size written inline when month selector used
  var largeCellSize = 22;

  // if set, choosing a day will send the date to this URL, eg 'someUrl?date='
  var urlBase = null;

  // show a cancel button to revert choice
  var showCancelLink = true;

  // stores link text to revert to when cancelling
  var _priorLinkText;

  // stores date before datepicker to revert to when cancelling
  var _priorDate;

  var id = pId;
  var lang = pLang;

  var calendar;
  var link;


  var en_months = 'January,February,March,April,May,June,July,August,September,October,November,December'.split(',');
  var fr_months = 'janvier,février,mars,avril,mai,juin,juillet,août,septembre,octobre,novembre,décembre'.split(',');

  var en_days = 'Sun,Mon,Tue,Wed,Thu,Fri,Sat'.split(',');
  var fr_days = 'Dim,Lun,Mar,Mer,Jeu,Ven,Sam'.split(',');

  var en_strings = new Array('Cancel', 'st', 'nd', 'rd', 'th');
  var fr_strings = new Array('Abandonner', 'er', '', '', '');

  var months;
  var days;
  var strings;

  if (lang == 'fr') {
    months = fr_months;
    days = fr_days;
    strings = fr_strings;
  } else {
    months = en_months;
    days = en_days;
    strings = en_strings;
  }

  /* Boring stuff to declare methods */
  this.toggleDatePicker = toggleDatePicker;
  this.cancel = cancel;
  this.unclipDates = unclipDates;
  this.changeCalendar = changeCalendar;
  this.setDate = setDate;
  this.pickDate = pickDate;
  this.getMonthName = getMonthName;
  this.dateFromAnsiDate = dateFromAnsiDate;
  this.ansiDateFromDate = ansiDateFromDate;
  this.getSelectedDate = getSelectedDate;
  this.makeChangeCalendarLink = makeChangeCalendarLink;
  this.formatDay = formatDay;
  this.writeMonth = writeMonth;
  this.writeYear = writeYear;
  this.selectMonth = selectMonth;
  this.selectYear = selectYear;
  this.writeCalendar = writeCalendar;

  /* Methods definition */

  function toggleDatePicker() {
    calendar = document.getElementById('datepicker_' + id + '_calendar');
    link = document.getElementById('datepicker_' + id + '_link');

    if (calendar.style.display == 'block') {  // If showing, hide
      calendar.style.display = 'none';
    } else {                                  // Else, show
      calendar.style.display = 'block';
      _priorLinkText = link.innerHTML;
      _priorDate = document.getElementById(id).value;
      this.writeCalendar();
    }
  }

  function cancel() {
    link.innerHTML = _priorLinkText;
    document.getElementById(id).value = _priorDate;
    calendar.style.display = 'none';
  }

  // mitigate clipping when new month has less days than selected date
  function unclipDates(d1, d2) {
    if (d2.getDate() != d1.getDate()) {
      d2 = new Date(d2.getFullYear(), d2.getMonth(), 0);
    }

    return d2;
  }

  // change date given an offset from the current date as a number of months (+-)
  function changeCalendar(offset) {
    var d1 = this.getSelectedDate(), d2;
    if (offset % 12 == 0) { // 1 year forward / back (fix Safari bug)
      d2 = new Date (d1.getFullYear() + offset / 12, d1.getMonth(), d1.getDate() );
    } else if (d1.getMonth() == 0 && offset == -1) {// tiptoe around another Safari bug
      d2 = new Date (d1.getFullYear() - 1, 11, d1.getDate() );
    } else {
      d2 = new Date (d1.getFullYear(), d1.getMonth() + offset, d1.getDate() );
    }

    d2 = this.unclipDates(d1, d2);
    ansi_date = d2.getFullYear() + '-' + (d2.getMonth() + 1) + '-' + d2.getDate();
    this.setDate(ansi_date);
    this.writeCalendar();
  }

  function setDate(ansiDate) {
    var d_day  = (showDaySuffixInLink ? this.formatDay(ansiDate.split('-')[2]) : ansiDate.split('-')[2]);
    var d_year = (abbreviateYearInLink ? ansiDate.split('-')[0].substring(2,4) : ansiDate.split('-')[0]);
    var d_mon  = this.getMonthName(Number(ansiDate.split('-')[1])-1);
    if (abbreviateMonthInLink) { d_mon = d_mon.substring(0, 3); }
    document.getElementById(id).value = ansiDate;
    link.innerHTML = d_day + ' ' + d_mon + ' ' +  d_year;
  }

  function pickDate(ansi_date) {
    this.setDate(ansi_date);
    this.toggleDatePicker();
    if (urlBase) {
      document.location.href = urlBase + ansi_date
    }
  }

  function getMonthName(monthNum) { //anomalous
    return months[monthNum];
  }

  function dateFromAnsiDate(ansi_date) {
    return new Date(ansi_date.split('-')[0], Number(ansi_date.split('-')[1]) - 1, ansi_date.split('-')[2])
  }

  function ansiDateFromDate(date) {
    alert( date.getFullYear() + '-' + (date.getMonth()+1) + '-' + date.getDate() );
  }

  function getSelectedDate() {
    if (document.getElementById(id).value == '') return new Date(); // default to today if no value exists
    return this.dateFromAnsiDate(document.getElementById(id).value);
  }

  function makeChangeCalendarLink(label, offset) {
    return ('<a href="#datepicker" onclick="obj_' + id + '.changeCalendar(' + offset + ')">' + label + '</a>');
  }

  function formatDay(n) {
    var x;
    switch (String(n)){
      case '1' :
      case '21': case '31': x = strings[1]; break;
      case '2' : case '22': x = strings[2]; break;
      case '3' : case '23': x = strings[3]; break;
      default:
        x = strings[4];
    }

    return n + x;
  }

  function writeMonth(n) {
    if (useDropForMonth) {
      var opts = '';
      for (i in months) {
        sel = (i == this.getSelectedDate().getMonth() ? 'selected="selected" ' : '');
        opts += '<option ' + sel + 'value="'+ i +'">' + this.getMonthName(i) + '</option>';
      }

      return '<select onchange="obj_' + id + '.selectMonth(this.value)">' + opts + '</select>';
    } else {
      return this.getMonthName(n);
    }
  }

  function writeYear(n) {
    if (useDropForYear) {
      var min = year - yearsPriorInDrop;
      var max = year + yearsNextInDrop;
      var opts = '';
      for (i = min; i < max; i++) {
        sel = (i == this.getSelectedDate().getFullYear() ? 'selected="selected" ' : '');
        opts += '<option ' + sel + 'value="'+ i +'">' + i + '</option>';
      }

      return '<select onchange="obj_' + id + '.selectYear(this.value)">' + opts + '</select>';
    } else {
      return n;
    }
  }

  function selectMonth(n) {
    d = this.getSelectedDate();
    d2 = new Date(d.getFullYear(), n, d.getDate());
    d2 = this.unclipDates(d, d2);
    this.setDate(d2.getFullYear() + '-' + (Number(n)+1) + '-' + d2.getDate() );
    this.writeCalendar();
  }

  function selectYear(n) {
    d = this.getSelectedDate(id);
    d2 = new Date(n, d.getMonth(), d.getDate());
    d2 = this.unclipDates(d, d2);
    this.setDate(n + '-' + (d2.getMonth()+1) + '-' + d2.getDate() );
    this.writeCalendar();
  }

  function writeCalendar() {
    var date = this.getSelectedDate();
    var firstWeekday = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    var lastDateOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    var day  = 1; // current day of month

    // not quite entirely pointless: fix Safari display bug with absolute positioned div
    link.innerHTML = link.innerHTML;

    var o = '<table cellspacing="1">'; // start output buffer
    o += '<thead><tr>';

    // month buttons
    o +=
      '<th style="text-align:left">' + this.makeChangeCalendarLink('&lt;',-1) + '</th>' +
      '<th colspan="5">' + (showDaySuffixInCalendar ? this.formatDay(date.getDate()) : date.getDate()) +
      ' ' + this.writeMonth(date.getMonth()) + '</th>' +
      '<th style="text-align:right">' + this.makeChangeCalendarLink('&gt;',1) + '</th>';
    o += '</tr><tr>';

    // year buttons
    o +=
      '<th colspan="2" style="text-align:left">' + this.makeChangeCalendarLink('&lt;&lt;',-12) + '</th>' +
      '<th colspan="3">' + this.writeYear(date.getFullYear()) + '</th>' +
      '<th colspan="2" style="text-align:right">' + this.makeChangeCalendarLink('&gt;&gt;',12) + '</th>';
    o += '</tr><tr class="day_labels">';

    // day labels
    for(var i = 0; i < days.length; i++) {
      o += '<th>' + days[(i + firstDayOfWeek) % 7] + '</th>';
    }
    o += '</tr></thead>';

    if (showCancelLink) {
      o += '<tfoot><tr><td colspan="7" class="cancel_butt"><a href="#datepicker" onclick="obj_' + id + '.cancel()">[x]&nbsp;' + strings[0] + '</a></td></tr></tfoot>';
    }

    // day grid
    o += '<tbody>';
    for(rows = 1; rows < 7 && (constantHeight || day < lastDateOfMonth); rows++) {
      o += '<tr>';
      for(var day_num = 0; day_num < days.length; day_num++) {
        var translated_day = (firstDayOfWeek + day_num) % 7
        if ((translated_day >= firstWeekday || day > 1) && (day <= lastDateOfMonth) ) {
          args = (date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + day);
          style = (selectMonth ? 'style="width: ' + largeCellSize + 'px"' : '');
          o +=
            '<td ' + style + '>' + // link : each day
            "<a href=\"#datepicker\" onclick=\"obj_" + id + ".pickDate('" + args + "'); return false;\">" + day + '</a>' +
            '</td>';
          day++;
        } else {
          o += '<td>&nbsp;</td>';
        }
      }

      o += '</tr></tbody>';
    }

    o += '</table>';

    calendar.innerHTML = o;
  }
}

function TimePicker(pId, pLang) {
  var version = 0.1;
  var currentHours = 0;
  var currentMinutes = 0;
  var currentSeconds = 0;
  var wantSeconds = false;
  var id = pId;
  var lang = pLang;

  this.enableSeconds = enableSeconds;
  this.init = init;
  this.toggleTimePicker = toggleTimePicker;
  this.cancel = cancel;
  this.makeChangeClockHourLink = makeChangeClockHourLink;
  this.makeChangeClockMinLink = makeChangeClockMinLink;
  this.makeChangeClockSecLink = makeChangeClockSecLink;
  this.changeClockHours = changeClockHours;
  this.changeClockMinutes = changeClockMinutes;
  this.changeClockSeconds = changeClockSeconds;
  this.getNewOffsetValue = getNewOffsetValue;
  this.getSelectedTime = getSelectedTime;
  this.timeFromFormatedTime = timeFromFormatedTime;
  this.setTime = setTime;
  this.writeHours = writeHours;
  this.writeMinutes = writeMinutes;
  this.writeSeconds = writeSeconds;
  this.initTime = initTime;
  this.writeClock = writeClock;
  this.pickTime = pickTime;
  this.getHoursElement = getHoursElement;
  this.getMinutesElement = getMinutesElement;
  this.getSecondsElement = getSecondsElement;
  this.getClockElement = getClockElement;
  this.getLinkElement = getLinkElement;


  /* Translation strings */
  var en_strings = new Array("hours", "minutes", "seconds", "h", "min", "s", "Valid", "Close");
  var fr_strings = new Array("heures", "minutes", "secondes", "h", "min", "s", "Valider", "Fermer");

  var strings;

  if (lang == 'fr') {
    strings = fr_strings;
  } else {
    strings = en_strings;
  }

  /* Method declarations */

  function enableSeconds() {
    wantSeconds = true;
  }

  function init() {
    hoursElt = document.getElementById('timepicker_' + id + '_hours');
    minutesElt = document.getElementById('timepicker_' + id + '_minutes');
    secondsElt = document.getElementById('timepicker_' + id + '_seconds');
    clockElt = document.getElementById('timepicker_' + id + '_clock');
    linkElt = document.getElementById('timepicker_' + id + '_link');
  }

  function toggleTimePicker() {
    var clock = this.getClockElement();

    if (clock.style.display == 'block') {  // If showing, hide
      clock.style.display = 'none';
    } else {                                  // Else, show
      clock.style.display = 'block';
      this.writeClock();
    }
  }

  function cancel() {
    this.getClockElement().style.display = 'none';
  }

  function makeChangeClockHourLink(label, offset) {
    return ('<a href="#timepicker" onclick="obj_' + id + '.changeClockHours(' + offset + ')">' + label + '</a>');
  }

  function makeChangeClockMinLink(label, offset) {
    return ('<a href="#timepicker" onclick="obj_' + id + '.changeClockMinutes(' + offset + ')">' + label + '</a>');
  }

  function makeChangeClockSecLink(label, offset) {
    return ('<a href="#timepicker" onclick="obj_' + id + '.changeClockSeconds(' + offset + ')">' + label + '</a>');
  }

  function changeClockHours(offset) {
    currentHours = this.getNewOffsetValue(currentHours, offset, 24);

    this.getHoursElement().innerHTML = this.writeHours();
  }

  function changeClockMinutes(offset) {
    currentMinutes = this.getNewOffsetValue(currentMinutes, offset, 60);

    this.getMinutesElement().innerHTML = this.writeMinutes();
  }

  function changeClockSeconds(offset) {
    currentSeconds = this.getNewOffsetValue(currentSeconds, offset, 60);

    this.getSecondsElement().innerHTML = this.writeSeconds();
  }

  function getNewOffsetValue(oldValue, offset, modulo) {
    newValue = oldValue + offset;

    if (newValue < 0) {
      newValue = modulo + newValue;
    } else {
      newValue = newValue % modulo;
    }

    return newValue;
  }

  function getSelectedTime() {
    if (document.getElementById(id).value == '') {
      date = new Date();
      date.setHours(0);
      date.setMinutes(0);
      date.setSeconds(0);
      return date
    }
    return this.timeFromFormatedTime(document.getElementById(id).value);
  }

  function timeFromFormatedTime(strTime) {
    date = new Date();
    date.setHours(strTime.split(':')[0]);
    date.setMinutes(strTime.split(':')[1]);
    if (wantSeconds) {
      date.setSeconds(strTime.split(':')[2]);
    }
    return date;
  }

  function setTime() {
    hiddenStr = currentHours + ':' + currentMinutes;
    displayStr = this.writeHours() + strings[3] + ' ' + this.writeMinutes() + strings[4];

    if (wantSeconds) {
      hiddenStr += ':' + currentSeconds;
      displayStr += ' ' + currentSeconds + strings[5];
    }

    document.getElementById(id).value = hiddenStr;
    this.getLinkElement().innerHTML = displayStr;
  }

  function writeHours() {
    var hours = currentHours.toString();
    if (hours.length < 2) {
      hours = '0' + hours;
    }

    return hours;
  }

  function writeMinutes() {
    var minutes = currentMinutes.toString();
    if (minutes.length < 2) {
      minutes = '0' + minutes;
    }

    return minutes;
  }

  function writeSeconds() {
    var seconds = currentSeconds.toString();
    if (seconds.length < 2) {
      seconds = '0' + seconds;
    }

    return seconds;
  }

  function initTime() {
    currentHours = this.getSelectedTime(id).getHours();
    currentMinutes = this.getSelectedTime(id).getMinutes();
    currentSeconds = this.getSelectedTime(id).getSeconds();
  }

  function writeClock() {
    this.initTime();

    var o = '<table cellspacing="1">'; // start output buffer
    o += '<tbody><tr><th colspan="5">' + strings[0] + '</th></tr>';

    // hours buttons
    o += '<tr>' +
      '<td style="text-align: left">' + this.makeChangeClockHourLink('&lt;&lt;', -6) + '</td>' +
      '<td style="text-align: left">' + this.makeChangeClockHourLink('&lt;', -1) + '</td>' +
      '<td class="timepicker-value-cell" id="timepicker_' + id + '_hours">' + this.writeHours() + '</td>' +
      '<td style="text-align: right">' + this.makeChangeClockHourLink('&gt;', 1) + '</td>' +
      '<td style="text-align: right">' + this.makeChangeClockHourLink('&gt;&gt;', 6) + '</td>'
    o += '</tr>';
    o += '<tr><th colspan="5">' + strings[1] + '</th></tr>';
    // Minutes buttons
    o += '<tr>' +
      '<td style="text-align: left">' + this.makeChangeClockMinLink('&lt;&lt;', -10) + '</td>' +
      '<td style="text-align: left">' + this.makeChangeClockMinLink('&lt;', -1) + '</td>' +
      '<td class="timepicker-value-cell" id="timepicker_' + id + '_minutes">' + this.writeMinutes() + '</td>' +
      '<td style="text-align: right">' + this.makeChangeClockMinLink('&gt;', 1) + '</td>' +
      '<td style="text-align: right">' + this.makeChangeClockMinLink('&gt;&gt;', 10) + '</td>'
    o += '</tr>';

    if (wantSeconds) {
      o += '<tr><th colspan="5">' + strings[2] + '</th></tr>';
      // Seconds buttons
      o += '<tr>' +
        '<td style="text-align: left">' + this.makeChangeClockSecLink('&lt;&lt;', -10) + '</td>' +
        '<td style="text-align: left">' + this.makeChangeClockSecLink('&lt;', -1) + '</td>' +
        '<td class="timepicker-value-cell" id="timepicker_' + id + '_seconds">' + this.writeSeconds() + '</td>' +
        '<td style="text-align: right">' + this.makeChangeClockSecLink('&gt;', 1) + '</td>' +
        '<td style="text-align: right">' + this.makeChangeClockSecLink('&gt;&gt;', 10) + '</td>'
        o += '</tr>';
    }

    o += '<tr>';
    o += '<td colspan="5"><div class="timepicker_valid_butt"><a href="#timepicker" onclick="obj_' + id + '.pickTime()">' + strings[6] + '</a></div>';
    o += '<div class="timepicker_cancel_butt"><a href="#timepicker" onclick="obj_' + id + '.cancel()">' + strings[7] + '</a></div></td>';
    o += '</tbody>';
    o += '</table>';

    this.getClockElement().innerHTML = o;
  }

  function pickTime() {
    this.setTime();
    this.toggleTimePicker();
  }

  function getHoursElement() {
    return document.getElementById('timepicker_' + id + '_hours');
  }

  function getMinutesElement() {
    return document.getElementById('timepicker_' + id + '_minutes');
  }

  function getSecondsElement() {
    return document.getElementById('timepicker_' + id + '_seconds');
  }

  function getClockElement() {
    return document.getElementById('timepicker_' + id + '_clock');
  }

  function getLinkElement() {
    return document.getElementById('timepicker_' + id + '_link');
  }
}
