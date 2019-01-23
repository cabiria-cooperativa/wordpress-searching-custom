jQuery(document).ready(function($) {
    
    //console.log('Plugin boilerplate loaded...');

    $('.cabi_searching_custom__field').focus(function(){
        $('.cabi_searching_custom').removeClass('cabi_searching_custom_field_error');
    })

    $('.cabi_searching_custom').submit(function(){
        var immobile_prezzo = $('.cabi_searching_custom_immobile_prezzo').val();
        var immobile_luogo = $('.cabi_searching_custom_immobile_luogo').val();
        var immobile_tipo_di_contratto = $('.cabi_searching_custom_immobile_tipo_di_contratto').val();
        var immobile_tipologia = $('.cabi_searching_custom_immobile_tipologia').val();
        var immobile_area = $('.cabi_searching_custom_immobile_area').val();
        //console.log(immobile_prezzo, immobile_luogo, immobile_tipo_di_contratto, immobile_tipologia, immobile_area);
        if (!immobile_prezzo && !immobile_luogo && !immobile_tipo_di_contratto && !immobile_tipologia &&!immobile_area) {
            $('.cabi_searching_custom').addClass('cabi_searching_custom_field_error');
            return false;
        }
    });

});