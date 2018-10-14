            var map = false;
            $(document).ready(function () {

                if ($('#map').length > 0) {
                    ymaps.ready(function () {

                        map = new ymaps.Map("map", {
                            center: [55.76, 37.64],
                            zoom: 3
                        });

                        map.behaviors.disable("searchbox");
                        map.behaviors.disable("scrollZoom");

                        initMapPointers();
                    });
                }

                function initMapPointers() {

                    var myCollection = new ymaps.GeoObjectCollection();

                    var k = 0, lt, ln;
                    $('.buy_scroll_list_item').each(function () {
                        k++;
                        var lat = lt = $(this).data('lat');
                        var lng = ln = $(this).data('lng');

                        var myPlacemark = new ymaps.GeoObject({
                            geometry: {
                                type: "Point",
                                coordinates: [lat, lng],
                            },
                            properties: {
                                id: $(this).attr('id'),
                                hintContent: $(this).data('name'),
                                balloonContent: '<h2>' + $(this).data('name') + '</h2><p>' + $(this).data('address') + '</p>' + $(this).data('text')
                            }
                        }, {
                            //preset: 'islands#blackStretchyIcon',
                            iconLayout: 'default#image',
                            //iconImageHref: 'http://bolstop.ci59704.tmweb.ru/test/map.png',
                            iconImageHref: '../upload/map.png',
                            iconImageSize: [50, 50]
                        });
                        myPlacemark.events.add('click', function () {
                            var nfid = myPlacemark.properties.get('id');
                            $('.buy_scroll_list_item').removeClass('active');
                            $('#' + nfid).addClass('active');
                            console.log($('#' + nfid).offset().top - 340);
                            $('.buy_scroll_list').scrollTop(0);
                            $('.buy_scroll_list').scrollTop($('#' + nfid).offset().top - 340-170-170);
                        });

                        myCollection.add(myPlacemark);
                    });
                    // Добавляем коллекцию на карту.
                    map.geoObjects.add(myCollection);

                    // Устанавливаем карте центр и масштаб так, чтобы охватить коллекцию целиком.
                    if (k > 1)
                        map.setBounds(myCollection.getBounds());
                    else map.setCenter([lt, ln], 17);

                }

                function mapCenter(lt, ln) {
                    map.setCenter([lt, ln], 17);
                }

                // Autoselect
                if (!$('.city_choised').hasClass('city_selected'))
                    ymaps.ready(function () {
                        ymaps.geolocation.get({provider: 'yandex'}).then(function (result) {
                            var city = result.geoObjects.get(0).properties.getAll().name;
                            console.log(city);
                            if ($('span#' + city).length > 0 && city != $('.city_choised').text())
                                $('span#' + city).click();
                        });
                    });

                $(document).on('click', '.buy_scroll_list_item', function () {
                    $('.buy_scroll_list_item').removeClass('active');
                    $(this).addClass('active');
                    mapCenter($(this).data('lat'), $(this).data('lng'));
                });

                $('.city_choised').click(function () {
                    $('.modal_city').toggleClass('active');
                    return false;
                });
                $('.changeCity').click(function () {
                    $('.city_choised').text($(this).text());
                    $('.modal_city').removeClass('active');
                    var data = "ajax=Y&city=" + $(this).data('id');
                    $.ajax({
                        url: '/lechebnye-uchrezhdeniya/index.php', // указываем URL и
                        dataType: "html", // тип загружаемых данных
                        method: "POST",
                        data: data,
                        success: function (data) { // вешаем свой обработчик на функцию success
                            //console.log(data);
                            //$('#map').html('');
                            $('.buy_scroll_list').html(data);
                            initMapPointers();
                        }
                    });
                    return false;
                });
            });
    