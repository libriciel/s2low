/*
 * jQuery File Upload Demo
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/* global $ */

$(function () {
  'use strict';

  // Initialize the jQuery File Upload widget:
  $('#fileupload').fileupload({
      multipart: true,
      singleFileUploads: false,
      acceptFileTypes: /(\.|\/)(pdf)$/i
  });
});