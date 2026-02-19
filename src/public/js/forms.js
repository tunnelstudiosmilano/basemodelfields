b4b.forms = {};

b4b.forms.validate_form = function () {
    var allow_submission = true;

    $('textarea.ckeditor').each(function () {
        var $textarea = $(this);
        $textarea.val(CKEDITOR.instances[$textarea.attr('name')].getData());
    });

    $('.main_form .required:input').each(function () {
        if ($(this).val().trim() == '') {
            allow_submission = false;
            $(this).parents('.form-group').addClass('has-error');
        } else {
            $(this).parents('.form-group').removeClass('has-error');
        }
    });

    if (!allow_submission) {
        $('.alert').show();
        $('html, body').animate({scrollTop: $('.has-error').first().offset().top - 10}, 'fast');
    }

    return allow_submission;
}

$(document).ready(function () {
    $('.main_form').submit(b4b.forms.validate_form);

    if ($('.datepicker').length) {
        var y = new Date().getFullYear();
        $('.datepicker').datepicker({dateFormat: 'dd/mm/yy', changeYear: true, yearRange: '1940:' + (y + 1)})
            .on('changeDate', function (ev) {
                var el = $(this).parent().next().children('.label-danger');
                if ($(el).hasClass('select')) {
                    $(el).trigger("click");
                }
            });
    }

    new CropperPreview(null);

    if($('.localized-fields').length){
        new LocalizedXlsImportExport('.localized-fields');
    }

});





var LocalizedXlsImportExport = function (box) {
    this.box = $(box);
    this.oFileIn;
    this.init();
};

$.extend(LocalizedXlsImportExport.prototype, {
    init: function () {
        var that = this;
        $( "<div id='localized-xls-handler' class='panel-default'><div class='panel-heading'><b>Localization import/export</b></div><div class='panel-body'></div></div>" ).insertBefore( this.box );
        $('#localized-xls-handler .panel-body').append( "<a href='#' id='download_localized_xls' class='btn btn-primary'>Download XLS</a>" );
        $('#localized-xls-handler .panel-body').append( "<label>Upload XLS: </label><input id='localized_xls_input' type='file' name='localized_xls'>" );


        $('#download_localized_xls').click(function (e) {
            e.preventDefault();
            that.download_localized_xls();
        })

        this.oFileIn = $('#localized_xls_input');
        this.oFileIn.change(function (e) {
            that.XLSPicked(e);
        });
    },
    download_localized_xls: function(){
        /*var data = [];
        var data_header = [];
        var l_f_cont = $('.localized-fields .panel-body');
        l_f_cont.each(function (i,item) {
            var fields = $(item).find('input[type="text"],textarea');
            var fields_obj = {};
            fields.each(function (j, field) {
                var name = $(field).attr('name').split('[');
                var lang = name[1].slice(0, -1);;
                name = name[0];

                if(i == 0){
                    if(j == 0){
                        data_header.push('lang');
                    }
                    data_header.push(name);
                }
                if(j == 0){
                    fields_obj['lang'] = lang;
                }
                fields_obj[name] = $(field).val();

            });

            data.push(fields_obj);
        });*/
        var data = [];
        var data_header = [];
        //var langs = [];
        //var fields = [];
        var l_f_conts = $('.localized-fields .panel-default');
        l_f_conts.each(function (i, item) {
            //langs.push($(item).data('lang'));
            if(i == 0){
                data_header.push('fields');
            }
            var inputs = $(item).find('input[type="text"],textarea');
            inputs.each(function (j, field) {
                var name = $(field).attr('name').split('[');
                var lang = name[1].slice(0, -1);
                name = name[0];

                //fields.push(name);
                if(!data.hasOwnProperty(j))data[j] = {};
                data[j]['fields'] = name;
                if($(field).hasClass('ckeditor')){

                    data[j][lang] = CKEDITOR.instances[$(field).attr('name')].getSnapshot();
                    //console.log(data[j][lang]);

                }else{
                    data[j][lang] = $(field).val();
                }



            });
            data_header.push($(item).data('lang'));
        });


        /*console.log(langs);
        console.log(fields);
        console.log(data_header);
        console.log(data);*/
        var file_name = ($('[name="code_name"]').length && $('[name="code_name"]').val()) ? $('[name="code_name"]').val().replace(/[^a-zA-Z0-9]/g, "")+'.xls' : 'out.xls';
        var ws = XLSX.utils.json_to_sheet(data, {header:data_header, skipHeader:false});
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "WorksheetName");
        XLSX.writeFile(wb, file_name);
    },
    XLSPicked: function (oEvent) {
        var that = this;
        var oFile = oEvent.target.files[0];
        var sFilename = oFile.name;
        // Create A File Reader HTML5
        var reader = new FileReader();

        // Ready The Event For When A File Gets Selected
        reader.onload = function(e) {
            var data = e.target.result;
            var cfb = XLS.CFB.read(data, {type: 'binary'});
            var wb = XLS.parse_xlscfb(cfb);
            // Loop Over Each Sheet
            wb.SheetNames.forEach(function(sheetName) {
                // Obtain The Current Row As CSV
                var oJS = XLS.utils.sheet_to_row_object_array(wb.Sheets[sheetName]);
                that.localizeFromJson(oJS)

            });
        };

        // Tell JS To Start Reading The File.. You could delay this if desired
        reader.readAsBinaryString(oFile);
    },
    localizeFromJson: function (oJS) {
        var that = this;
        $.each( oJS, function( key, row ) {
            var lenght = Object.keys(row).length;
            var i = 0;

            $.each( row, function( key, cel ) {
                if( i != 0 && i < lenght){
                    var field = row['fields'];
                    var lang = key;
                    var input_name = field+'['+lang+']';
                    //console.log(input_name);
                    var input_field = $('.localized-fields .panel-default [name="'+input_name+'"]');
                    if(input_field.hasClass('ckeditor')){
                        CKEDITOR.instances[input_name].setData(cel);
                    }else{
                        input_field.val(cel);
                    }
                    //console.log(input_field);
                }
                i++;
            });
        });
        that.oFileIn.val('');
    }
});



var CropperPreview = function (ko) {
    this.ko = ko;
    this.items = $('.cropper-preview-cont');
    this.image = $('#modal-cropper-image');
    this.cropper = null;
    this.cropBoxData;
    this.canvasData;
    this.current;
    this.current_img;
    this.loading_overlay = false;

    this.init();
    this.initCropper();
};

$.extend(CropperPreview.prototype, {
    init: function () {
        var self = this;
        this.items.each(function (index, el) {
            $(el).find('input').change(function () {
                self.current = $(el);
                self.readURL(this);
            });

            var dest = $(el).children('.cropper-image-data');

            var imageData = dest.data('value');
            self.getBase64Image(imageData, function(base64Img){
                var img = $('<img />', {
                    src: base64Img,
                });
                img.appendTo($(el).children('.cropper-preview'));
            });
        });
    },
    getBase64Image: function (url, callback) {
        var img = new Image();
        img.crossOrigin = 'Anonymous';
        img.onload = function() {
            var canvas = document.createElement('CANVAS');
            var ctx = canvas.getContext('2d');
            var dataURL;
            canvas.height = this.height;
            canvas.width = this.width;
            ctx.drawImage(this, 0, 0);
            dataURL = canvas.toDataURL('Canvas');
            callback(dataURL);
            canvas = null;
        };
        img.src = url;

    },
    readURL: function (input) {
        var self = this;
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            if (input.files[0].size > 4100000) {
                alert('L\'imagine caricata non può superare i 4MB. carica un\'altra immagine');
                $(input).val('');
                return;
            }
            reader.onload = function (item) {
                var image = new Image();
                image.src = item.target.result;
                image.onload = function () {
                    /*if(this.width < 253 || this.height < 253){
                     alert('Immagine di dimensioni troppo ridotte');
                     $(input).val('');
                     return;
                     }*/
                    self.imageLoaded(image, input);
                };
            };
            reader.readAsDataURL(input.files[0]);
        }
    },
    imageLoaded: function (image, input) {
        var cont = $(input).parents('.cropper-preview-cont');

        this.current_img = image.src;

        $('#modal-cropper-image').attr('src', image.src);

        if (!this.loading_overlay) {
            $("body").append("<div id='cropper-loading-overlay'></div>");
            $('#cropper-loading-overlay').append('<div class="sk-fading-circle">\
				<div class="sk-circle1 sk-circle"></div>\
				<div class="sk-circle2 sk-circle"></div>\
				<div class="sk-circle3 sk-circle"></div>\
				<div class="sk-circle4 sk-circle"></div>\
				<div class="sk-circle5 sk-circle"></div>\
				<div class="sk-circle6 sk-circle"></div>\
				<div class="sk-circle7 sk-circle"></div>\
				<div class="sk-circle8 sk-circle"></div>\
				<div class="sk-circle9 sk-circle"></div>\
				<div class="sk-circle10 sk-circle"></div>\
				<div class="sk-circle11 sk-circle"></div>\
				<div class="sk-circle12 sk-circle"></div>\
				</div>');
            this.loading_overlay = $('#cropper-loading-overlay');
        } else {
            this.loading_overlay.show();
        }


        $('#cropper-modal-cont').modal({
            show: 'true',
            backdrop: 'static',
            keyboard: false
        });
    },
    initCropper: function () {
        var self = this;

        /*$('#cropper-modal-cont').on('hidden.bs.modal', function () {
            if (navigator.userAgent.indexOf('Edge') > -1 || (/Trident.*rv[ :]*11\./).test(navigator.userAgent)) {
                $('input:last').focus();
                self.current.attr("tabindex", -1).focus();
            } else {
                self.current.attr("tabindex", -1).focus();
                $("html, body").delay(300).animate({scrollTop: self.current.offset().top - 250}, 100);
            }

        });*/

        $('#cropper-modal-cont').on('shown.bs.modal', function () {
            var preview = $(self.current.find('.cropper-preview'));
            var aspectRatio = $(self.current).attr('data-width') / $(self.current).attr('data-height');
            $(self.image).cropper({
                aspectRatio: aspectRatio,
                viewMode: 1,
                dragMode: 'move',
                restore: false,
                guides: false,
                highlight: false,
                cropBoxMovable: false,
                cropBoxResizable: false,
                //preview:preview,
                built: function () {
                    self.image.cropper('setCanvasData', self.canvasData);
                    self.image.cropper('setCropBoxData', self.cropBoxData);
                    self.cropper = $(self.image).cropper().data().cropper;
                    self.cropper.reset();
                    if ($(self.current).hasClass('cropper-rounded')) {
                        $('.cropper-view-box, .cropper-face').css({'border-radius': '50%'});
                    } else {
                        $('.cropper-view-box, .cropper-face').css({'border-radius': '0%'});
                    }
                }
            });
        });

        $('.cropper-zoom-in').click(function () {
            self.image.cropper("zoom", 0.1);
        });
        $('.cropper-zoom-out').click(function () {
            self.image.cropper("zoom", -0.1);
        });
        $('.cropper-cancel').click(function () {
            $(self.current).find('input').val('');
            self.cropCancelled();
            setTimeout(function () {
                self.loading_overlay.hide();
            }, 200);

        });
        $('.cropper-rotate').click(function () {
            self.image.cropper("rotate", 10);
        });

        $('.cropper-ok').click(function () {
            $(self.current).find('input').val('');
            self.cropped();
            setTimeout(function () {
                self.loading_overlay.hide();
            }, 200);
        });
    },
    cropped: function () {
        var self = this;
        self.cropBoxData = self.image.cropper('getCropBoxData');
        self.canvasData = self.image.cropper('getCanvasData');
        var preview = $(self.current.find('.cropper-preview'));
        var final_w = ($(this.current).attr('data-width')) ? $(this.current).attr('data-width') : $(preview).outerWidth();
        var final_h = ($(this.current).attr('data-height')) ? $(this.current).attr('data-height') : $(preview).outerHeight();

        var img_cropped = self.image.cropper('getCroppedCanvas', {
            width: final_w,
            height: final_h
        }).toDataURL("image/jpeg");
        var img = $('<img />', {
            src: img_cropped,
            alt: 'immagine profilo'
        });

        self.image.cropper('destroy');
        $(preview).empty();
        img.appendTo($(preview));
        self.mapModelData(self.current_img, img_cropped, self.current);
    },
    cropCancelled: function () {
        var self = this;
        setTimeout(function () {
            self.image.cropper('destroy');
        }, 500);
    },
    mapModelData: function (img, img_cropped, current) {
        var dest = current.children('.cropper-image-data');
        dest.val(img_cropped);
    }

});

