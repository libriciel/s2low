import $ from 'jquery';
import 'jquery-ui/ui/widget';
import 'jquery-ui/ui/position';

import 'jquery-ui/ui/widgets/menu';
import 'jquery-ui/ui/widgets/autocomplete';
import 'jquery-ui/ui/widgets/datepicker';
import 'jquery-ui/ui/widgets/tooltip';

import 'jquery-ui/themes/base/core.css';
import 'jquery-ui/themes/base/autocomplete.css';
import 'jquery-ui/themes/base/datepicker.css';
import 'jquery-ui/themes/base/menu.css';
import 'jquery-ui/themes/base/tooltip.css';
import 'jquery-ui/themes/base/theme.css';

// Datepicker FR
import 'jquery-ui/ui/i18n/datepicker-fr';
// Timepicker
import 'jquery-timepicker/jquery.timepicker.css';
import 'jquery-timepicker/jquery.timepicker.js';

window.$ = window.jQuery = $;

$.datepicker.setDefaults($.datepicker.regional['fr']);
