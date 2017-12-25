$(function() {
    //左侧导航
    var Accordion = function(el, multiple) {
        this.el = el || {};
        this.multiple = multiple || false;
        // Variables privadas
        var links = this.el.find('.link');
        var submenu = this.el.find('.submenu').find('a');
        // Evento
        links.on('click', {el: this.el, multiple: this.multiple}, this.dropdown);
        submenu.on('click',function(){
            $(this).addClass('link_a').parent('dd').siblings().find('a').removeClass('link_a');
            $(this).addClass('link_a').parents('.navBox li').siblings().find('a').removeClass('link_a');
        });
        //console.log(submenu);
    };
    Accordion.prototype.dropdown = function(e) {
        var $el = e.data.el;
        $this = $(this);
        $next = $this.next();

        $next.slideToggle();
        $this.parent().toggleClass('open');
        //$el.find('.submenu a').removeClass('link_a');
        //console.log($this.parent().find('.submenu a'));

        if (!e.data.multiple) {
            $el.find('.submenu').not($next).slideUp().parent().removeClass('open');
        }
    };

    var accordion = new Accordion($('#accordion'), false);
});