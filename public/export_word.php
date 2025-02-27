<?php
require_once '../config/db.php';
require_once '../src/Database.php';
require_once '../libs/phpword/src/PhpWord/Autoloader.php';

\PhpOffice\PhpWord\Autoloader::register();
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\Shared\Converter;


$db = new Database($dbConfig);
$connection = $db->getConnection();

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$periodText = (!empty($startDate) && !empty($endDate)) ? "$startDate - $endDate" : "весь период";

$whereClause = "";
if (!empty($startDate) && !empty($endDate)) {
    $whereClause = " AND o.created_at BETWEEN :start_date AND :end_date";
}

function fetchData($connection, $query, $startDate, $endDate, $whereClause) {
    $statement = $connection->prepare($query);
    if (!empty($whereClause)) {
        $statement->bindValue(':start_date', $startDate);
        $statement->bindValue(':end_date', $endDate);
    }
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

$products = fetchData($connection, "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE 1=1 $whereClause GROUP BY p.name ORDER BY total_sold DESC", $startDate, $endDate, $whereClause);

$categories = fetchData($connection, "SELECT p.category, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE 1=1 $whereClause GROUP BY p.category ORDER BY total_revenue DESC", $startDate, $endDate, $whereClause);

$users = fetchData($connection, "SELECT u.username, COUNT(o.id) as total_orders, SUM(o.total_price) as total_spent FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1 $whereClause GROUP BY u.username ORDER BY total_orders DESC", $startDate, $endDate, $whereClause);

// $format = $_GET['format'] ?? 'word';


    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    $section->addText("Отчет по аналитике за $periodText", ['bold' => true, 'size' => 14]);
    $section->addTextBreak(1);
    
    function addTableToWord($section, $title, $data, $headers) {
        $section->addText($title, ['bold' => true, 'size' => 12]);
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80];
        $phpWord = new PhpWord();
        $phpWord->addTableStyle('StyledTable', $tableStyle);
        $table = $section->addTable('StyledTable');
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(Converter::inchToTwip(1.5))->addText($header, ['bold' => true]);
        }
        foreach ($data as $row) {
            $table->addRow();
            foreach ($row as $cell) {
                $table->addCell(Converter::inchToTwip(1.5))->addText($cell);
            }
        }
        $section->addTextBreak(2);
    }

    addTableToWord($section, "Продажи по товарам", $products, ["Товар", "Продано", "Выручка"]);
    addTableToWord($section, "Оборот по категориям", $categories, ["Категория", "Продано", "Выручка"]);
    addTableToWord($section, "Заказы по пользователям", $users, ["Пользователь", "Заказов", "Сумма"]);

    $file = '../exports/analytics.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($file);
    header('Content-Disposition: attachment; filename="analytics.docx"');
    readfile($file);
exit;
