+function ($) { "use strict";
    var ProjectPreview = function () {
        var self = this
        $(document).on('shown.bs.tab', '#md-preview-tab', function(){
            self.refreshPreview(this)
        })
    }

    ProjectPreview.prototype.refreshPreview = function(el) {
        var $preview = $('.user-conversation-preview-content'),
            $form = $(el).closest('form')

        $preview.html('<p>Loading preview... </p>');

        $form.request('onRefreshConversationPreview', {
            success: function(data) {
                $preview.html(data.preview)
                $('#md-preview-scroll').data('oc.scrollbar').update()
            }
        })
    }

    $(document).ready(function(){
        new ProjectPreview()
    })

}(window.jQuery);