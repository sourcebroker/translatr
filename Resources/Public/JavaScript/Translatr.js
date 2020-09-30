"use strict";

define(['jquery', 'select2'], function ($) {
  var Translatr = {};

  Translatr.init = function () {
    $('#field-extension, #field-sys_language_uid').select2();
    $('.js-translatr-form').on('submit', function (e) {
      e.preventDefault();
      window.location.href = $(this).attr('action') + '&' + $(this).serialize();
    });
  };

  Translatr.init();
  return Translatr;
});
