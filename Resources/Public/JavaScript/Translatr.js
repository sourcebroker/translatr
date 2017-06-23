define(['jquery', 'select2'], function($) {
    'use strict';

    var Translatr = {

    };

    Translatr.init = function() {
        $('#field-extension, #field-sys_language_uid').select2();
    };

    Translatr.init();

    return Translatr;
});