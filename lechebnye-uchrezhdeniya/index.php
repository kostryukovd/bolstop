<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Карта лечебно-профилактических учреждений");
$APPLICATION->SetPageProperty("title", "Карта лечебно-профилактических учреждений");
?>
    <link href="/lechebnye-uchrezhdeniya/style.css?_t=<?= time() ?>" type="text/css" rel="stylesheet"/>
<?
CModule::IncludeModule('iblock');
$arSelect = Array("ID", "IBLOCK_ID", "NAME");
$arFilter = Array("IBLOCK_ID" => "8", "ACTIVE" => "Y");
$res = Bitrix\Iblock\SectionTable::GetList(['order' => ['NAME' => 'asc'], 'filter' => ['IBLOCK_ID' => '8']]);
while ($fields = $res->Fetch()) {
    if ($fields['IBLOCK_ID'] == 8)
        $sections[$fields['ID']] = $fields['NAME'];
}
$iscityselect = (isset($_REQUEST['city'])) ? true : false;
if (!$_REQUEST['city']) $_REQUEST['city'] = 24;
$arSelect = Array("ID", "IBLOCK_ID", "NAME", "PREVIEW_TEXT", "PROPERTY_*");//IBLOCK_ID и ID обязательно должны быть указаны, см. описание arSelectFields выше
$arFilter = Array("IBLOCK_ID" => "8", "SECTION_ID" => $_REQUEST['city'], "ACTIVE" => "Y");
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize" => 50), $arSelect);
while ($ob = $res->GetNextElement()) {
    $fields = $ob->GetFields();
    $fields['PROPS'] = $ob->GetProperties();
    $clinics[] = $fields;
}
?>
    <div class="x-container">
        <!--
        <div class="breadcrumbs"><a class="breadcrumbs__item" href="/"><span class="breadcrumbs__icon icon__home"></span><span class="breadcrumbs__text">Главная</span></a><div class="breadcrumbs__separator">/</div><div class="breadcrumbs__item">
        <div class="breadcrumbs__text">Карта ЛПУ</div></div></div>
        -->

        <h1 class="content-title">Карта лечебно-профилактических учреждений</h1>
        <br />

        <div class="buy_container">
            <div class="left_block">
                <div class="buy_search">
                    <div class="buy_search_top">
                        <img src="https://www.skincap.ru/theme/images/mark.png" class="mark">
                        Ваш город:
                        <a href="#" class="city_choised<?=($iscityselect) ? ' city_selected':''?>">
                            <?=$sections[$_REQUEST['city']]?>
                        </a>
                    </div>
                </div>
                <div class="buy_scroll_list">
                    <? if ($_REQUEST['ajax'] == 'Y'): ?>
                        <? global $APPLICATION;
                        $APPLICATION->RestartBuffer(); ?>
                    <? endif; ?>
                    <? foreach ($clinics as $clinic): ?>
                        <? $coord = explode(',', $clinic['PROPS']['COORD']['VALUE']); ?>
                        <div id="<?= $clinic['ID'] ?>" data-text="<?= $clinic['PREVIEW_TEXT'] ?>"
                             data-address="<?= $clinic['PROPS']['ADDRESS']['VALUE'] ?>"
                             data-name="<?= $clinic['NAME'] ?>" data-lat="<?= $coord[0] ?>" data-lng="<?= $coord[1] ?>"
                             class="buy_scroll_list_item">
                            <div class="title"><b><?= $clinic['NAME'] ?></b></div>
                            <div class="place"><?= $clinic['PROPS']['ADDRESS']['VALUE'] ?></div>
                        </div>
                    <? endforeach; ?>
<!--
                    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
-->
                    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>

                    <? if ($_REQUEST['ajax'] == 'Y'): ?>
                        <? die(); ?>
                    <? endif; ?>
                </div>
            </div>
            <div class="modal_city">
                <div class="wrap">
                    <? foreach ($sections as $id => $city): ?>
                        <span class="changeCity" id="<?= $city ?>" data-id="<?= $id ?>"><?= $city ?></span>
                    <? endforeach; ?>
                </div>
            </div>
            <div id="map" class="buy_map"></div>

        </div>

        <script type="text/javascript">
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
        </script>


    </div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>