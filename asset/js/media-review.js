(function($) {
    function getRotationDegrees(obj) {
        var matrix = obj.css("-webkit-transform") ||
        obj.css("-moz-transform")    ||
        obj.css("-ms-transform")     ||
        obj.css("-o-transform")      ||
        obj.css("transform");
        if(matrix !== 'none') {
            var values = matrix.split('(')[1].split(')')[0].split(',');
            var a = values[0];
            var b = values[1];
            var angle = Math.round(Math.atan2(b, a) * (180/Math.PI));
        } else { var angle = 0; }
        return (angle < 0) ? angle + 360 : angle;
    }

    function setRotation(obj, direction) {
        var currentRotation = getRotationDegrees(obj);
        var newRotation = (direction == 'left') ? currentRotation - 90 : currentRotation + 90;
        obj.css('transform', 'rotate(' + newRotation + 'deg)');
    }

    $(document).ready(function() {
        if ($('.image.panzoom-container').length > 0) {
            var storedPanzoomStyle = '';
            var storedRotateStyle = '';
            $('.full-screen').featherlight('.wikitext-featherlight', {
                beforeOpen: function() {
                    $('#wikitext .media-render').panzoom('destroy');
                },
                afterOpen: function() {
                    var $zoomContainer = $('.featherlight-content');
                    $('.featherlight-content .media-render').panzoom({
                        $zoomIn: $zoomContainer.find(".zoom-in"),
                        $zoomOut: $zoomContainer.find(".zoom-out"),
                        $reset: $zoomContainer.find(".reset")
                    });
                },
                beforeClose: function() {
                    storedPanzoomStyle = $('.featherlight-content .media-render').attr('style');
                    storedRotateStyle = $('.featherlight-content .panzoom-container img').attr('style');
                    $('.featherlight-content .media-render').panzoom('destroy');
                    $('#wikitext .media-render').attr('style', storedPanzoomStyle);
                    $('#wikitext .panzoom-container img').attr('style', storedRotateStyle);
                },
                afterClose: function() {
                    var $zoomContainer = $('#wikitext');
                    $('#wikitext .media-render').panzoom({
                        $zoomIn: $zoomContainer.find(".zoom-in"),
                        $zoomOut: $zoomContainer.find(".zoom-out"),
                        $reset: $zoomContainer.find(".reset")
                    });
                }
            });
    
            $('.panzoom-container').on('click', '.rotate-left', function() {
                var panzoomImg = $(this).parents('.panzoom-container').find('img');
                setRotation(panzoomImg, 'left');
            });
    
            $('.panzoom-container').on('click', '.rotate-right', function() {
                var panzoomImg = $(this).parents('.panzoom-container').find('img');
                setRotation(panzoomImg, 'right');
            });
    
            $('.panzoom-container').on('click', '.reset', function() {
                var panzoomImg = $(this).parents('.panzoom-container').find('img');
                panzoomImg.css('transform', 'none');
            });
        } else {
            $('.full-screen').featherlight('.wikitext-featherlight');
        }

        function toggleExpirationSelect(protectionSelect) {
            var expirySelect = $('[name="protection_expiry"]');
            var currentValue = protectionSelect.find('option:selected').val();
            if (currentValue && currentValue != "all") {
                expirySelect.show();
            } else {  
                expirySelect.hide();
            }
        }

        var protectionSelect = $('[name="protection_level"]');
        toggleExpirationSelect(protectionSelect);
        protectionSelect.on('change', function() {
            toggleExpirationSelect($(this));
        });

        $('#content').on('click', '.watchlist.button', function(e) {
            e.preventDefault();
            var watchlistIcon = $(this);
            $(this).toggleClass('watched')
            var watchlistHiddenValue = $(this).next('[type="hidden"]');
            if (watchlistHiddenValue.val() == 0) {
                watchlistIcon.attr('aria-label', Omeka.jsTranslate('Watch media'));
                watchlistIcon.attr('title', Omeka.jsTranslate('Watch media'));
                watchlistHiddenValue.attr('value', 1);
            } else {
                watchlistIcon.attr('aria-label', Omeka.jsTranslate('Stop watching media'));
                watchlistIcon.attr('title', Omeka.jsTranslate('Stop watching media'));
                watchlistHiddenValue.attr('value', 0);
            }
        });
    });

})(jQuery)