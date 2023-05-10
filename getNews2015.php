<? require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('iblock');

header('Content-Type: application/json');

/**
 * Необходимо передавать $_GET параметр page
 * для пагинации - если делать запрос по году то будет много элементов
 * и подумал что лучше ограничить вывод по 10 элементов на странице
 * Фильтр сделал по дате создания новости
 * */
$page = 1;
if(isset($_REQUEST['page'])){
    $page = intval($_REQUEST['page']);
}
$iBlockId = 12;
$arSections = [];
//Получаем все разделы и собираем в массив
//всего скорей есть лучше решение, но лучше не придумал
$querySections = CIBlockSection::GetList(
    ["SORT"=>"ASC"],
    ["IBLOCK_ID"=>$iBlockId],
    false,
    ["ID", "NAME"]
);

while($itemSections = $querySections->GetNext())
{
    $arSections[$itemSections['ID']] = $itemSections;
}

$arResult = [];
$query = CIBlockElement::GetList(
    ["sort"=>"asc"],
    [ "IBLOCK_ID"=>$iBlockId, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y",
        '>=DATE_CREATE' => '01.01.2015','<=DATE_CREATE' => '31.12.2015 23:59:59'],
    false,
    array(
        'nTopCount' => false,
        'nPageSize' => 10, //количество элементов на странице
        'iNumPage' => $page, //текущая страница
        'checkOutOfRange' => true
    ),
    [ "ID", "IBLOCK_SECTION_ID", "DATE_CREATE", "IBLOCK_SECTION_NAME", "DETAIL_PAGE_URL", "PREVIEW_PICTURE",
        "NAME", "DATE_ACTIVE_FROM", "PROPERTY_AUTHOR_VALUE"]
);

while($element = $query->GetNextElement()){
    $fields = $element->GetFields();
    $props = $element->GetProperties();
    $dateCreate = CIBlockFormatProperties::DateFormat(
        'j F Y h:m',
        MakeTimeStamp(
            $fields["DATE_CREATE"],
            CSite::GetDateFormat()
        )
    );
    $author = CIBlockElement::GetByID($props['AUTHOR']['VALUE'])->GetNext();
    $arResult[] = [
        "id"=>$fields['ID'],
        "url"=>$fields['DETAIL_PAGE_URL'],
        "image"=>CFile::GetPath($fields['PREVIEW_PICTURE']),
        "name"=>$fields['NAME'],
        "section_name"=> isset($arSections[$fields['IBLOCK_SECTION_ID']]) ? $arSections[$fields['IBLOCK_SECTION_ID']]['NAME'] : null,
        "date"=> CIBlockFormatProperties::DateFormat(
            'j F Y h:m',
            MakeTimeStamp(
                $fields["DATE_CREATE"],
                CSite::GetDateFormat()
            )
        ),
        "author" => !empty($author)? $author['NAME'] : null,
        "tags" => $props['TAGS']['~VALUE']
    ];
}
$json = json_encode([
    "data" => $arResult,
    "count_pages" => $query->NavPageCount
]);

echo $json;
