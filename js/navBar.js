$(document).ready(function(){
    $('.hb-button').on('click', function(){
        $('nav ul').toggleClass('show');
    });
});

$(document).ready(function(){
    $('nav ul li a').on('click', function(){
        $('nav ul').removeClass();
    });
});

$(document).ready(function(){
    $('html, body').on('scroll', function(){
        $('nav ul').removeClass();
    });
});
