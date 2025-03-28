<?php
require_once '../../includes/session_check.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';  // Para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment};

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_visitas.xlsx"');
header('Cache-Control: max-age=0');

try {
    $database = new Database();
    $db = $database->connect();

    // Obtener rango de fechas
    $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $end_date = $_GET['end_date'] ?? date('Y-m-d');

    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    
    // Configurar la primera hoja - Resumen
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Resumen');

    // Estilo para encabezados
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2563EB']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Estadísticas generales
    $sheet->setCellValue('A1', 'Reporte de Visitas');
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $sheet->setCellValue('A2', 'Período:');
    $sheet->setCellValue('B2', date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date)));
    $sheet->mergeCells('B2:F2');

    // Encabezados de estadísticas
    $sheet->setCellValue('A4', 'Estadísticas Generales');
    $sheet->mergeCells('A4:F4');
    $sheet->getStyle('A4')->getFont()->setBold(true);

    // Obtener estadísticas
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_visits,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_visits,
            SUM(CASE WHEN status = 'in_route' THEN 1 ELSE 0 END) as in_route_visits,
            AVG(TIMESTAMPDIFF(MINUTE, CONCAT(visit_date, ' ', visit_time), completion_time)) as avg_completion_time
        FROM visits 
        WHERE visit_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agregar estadísticas
    $statsRows = [
        ['Total de Visitas', $stats['total_visits']],
        ['Visitas Completadas', $stats['completed_visits']],
        ['Visitas Pendientes', $stats['pending_visits']],
        ['Visitas En Ruta', $stats['in_route_visits']],
        ['Tiempo Promedio (horas)', round($stats['avg_completion_time'] / 60, 1)]
    ];

    $row = 5;
    foreach ($statsRows as $statRow) {
        $sheet->setCellValue('A' . $row, $statRow[0]);
        $sheet->setCellValue('B' . $row, $statRow[1]);
        $row++;
    }

    // Segunda hoja - Detalle de Visitas
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Detalle de Visitas');

    // Encabezados
    $headers = [
        'A1' => 'Fecha',
        'B1' => 'Hora',
        'C1' => 'Cliente',
        'D1' => 'Técnico',
        'E1' => 'Estado',
        'F1' => 'Tipo de Servicio',
        'G1' => 'Dirección',
        'H1' => 'Tiempo de Completado'
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    // Obtener datos de visitas
    $stmt = $db->prepare("
        SELECT 
            v.visit_date,
            v.visit_time,
            v.client_name,
            u.full_name as technician_name,
            v.status,
            v.service_type,
            v.address,
            TIMESTAMPDIFF(MINUTE, CONCAT(v.visit_date, ' ', v.visit_time), v.completion_time) as completion_minutes
        FROM visits v
        LEFT JOIN users u ON v.technician_id = u.id
        WHERE v.visit_date BETWEEN :start_date AND :end_date
        ORDER BY v.visit_date, v.visit_time
    ");
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Llenar datos
    $row = 2;
    foreach ($visits as $visit) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($visit['visit_date'])));
        $sheet->setCellValue('B' . $row, date('H:i', strtotime($visit['visit_time'])));
        $sheet->setCellValue('C' . $row, $visit['client_name']);
        $sheet->setCellValue('D' . $row, $visit['technician_name']);
        $sheet->setCellValue('E' . $row, $visit['status']);
        $sheet->setCellValue('F' . $row, $visit['service_type']);
        $sheet->setCellValue('G' . $row, $visit['address']);
        $sheet->setCellValue('H' . $row, $visit['completion_minutes'] ? round($visit['completion_minutes'] / 60, 1) . 'h' : '-');
        $row++;
    }

    // Autoajustar columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Tercera hoja - Rendimiento por Técnico
    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Rendimiento Técnicos');

    // Encabezados
    $headers = [
        'A1' => 'Técnico',
        'B1' => 'Total Visitas',
        'C1' => 'Completadas',
        'D1' => 'Tasa de Completitud',
        'E1' => 'Tiempo Promedio',
        'F1' => 'Visitas por Día',
        'G1' => 'Días Activos',
        'H1' => 'Cancelaciones'
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    // Obtener estadísticas detalladas por técnico
    $stmt = $db->prepare("
        SELECT 
            u.full_name,
            COUNT(v.id) as total_visits,
            SUM(CASE WHEN v.status = 'completed' THEN 1 ELSE 0 END) as completed_visits,
            AVG(CASE WHEN v.status = 'completed' 
                THEN TIMESTAMPDIFF(MINUTE, CONCAT(v.visit_date, ' ', v.visit_time), v.completion_time)
                ELSE NULL END) as avg_completion_time,
            COUNT(DISTINCT v.visit_date) as active_days,
            SUM(CASE WHEN v.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_visits,
            MIN(v.visit_date) as first_visit,
            MAX(v.visit_date) as last_visit
        FROM users u
        LEFT JOIN visits v ON u.id = v.technician_id 
            AND v.visit_date BETWEEN :start_date AND :end_date
        WHERE u.role = 'technician'
        GROUP BY u.id, u.full_name
        ORDER BY completed_visits DESC
    ");

    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Llenar datos de técnicos
    $row = 2;
    foreach ($technicians as $tech) {
        // Calcular métricas adicionales
        $completion_rate = $tech['total_visits'] > 0 
            ? ($tech['completed_visits'] / $tech['total_visits']) * 100 
            : 0;
        
        $days_between = (strtotime($tech['last_visit']) - strtotime($tech['first_visit'])) / (60 * 60 * 24) + 1;
        $visits_per_day = $days_between > 0 ? $tech['total_visits'] / $days_between : 0;

        // Llenar la fila
        $sheet->setCellValue('A' . $row, $tech['full_name']);
        $sheet->setCellValue('B' . $row, $tech['total_visits']);
        $sheet->setCellValue('C' . $row, $tech['completed_visits']);
        $sheet->setCellValue('D' . $row, round($completion_rate, 1) . '%');
        $sheet->setCellValue('E' . $row, $tech['avg_completion_time'] 
            ? round($tech['avg_completion_time'] / 60, 1) . 'h' 
            : '-');
        $sheet->setCellValue('F' . $row, round($visits_per_day, 1));
        $sheet->setCellValue('G' . $row, $tech['active_days']);
        $sheet->setCellValue('H' . $row, $tech['cancelled_visits']);

        // Aplicar formato condicional para tasa de completitud
        $sheet->getStyle('D' . $row)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $completion_rate >= 90 ? '90EE90' : 
                                   ($completion_rate >= 75 ? 'FFD700' : 'FFB6C1')]
            ]
        ]);

        $row++;
    }

    // Agregar fila de totales
    $row++;
    $sheet->setCellValue('A' . $row, 'TOTALES');
    $sheet->setCellValue('B' . $row, '=SUM(B2:B' . ($row-1) . ')');
    $sheet->setCellValue('C' . $row, '=SUM(C2:C' . ($row-1) . ')');
    $sheet->setCellValue('D' . $row, '=AVERAGE(D2:D' . ($row-1) . ')');
    $sheet->setCellValue('F' . $row, '=AVERAGE(F2:F' . ($row-1) . ')');
    $sheet->setCellValue('G' . $row, '=AVERAGE(G2:G' . ($row-1) . ')');
    $sheet->setCellValue('H' . $row, '=SUM(H2:H' . ($row-1) . ')');

    // Estilo para la fila de totales
    $sheet->getStyle('A'.$row.':H'.$row)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'color' => ['rgb' => 'F0F0F0']
        ]
    ]);

    // Agregar gráfico de rendimiento
    $dataSeriesLabels = [
        new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Rendimiento Técnicos!$C$1', null, 1),
        new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Rendimiento Técnicos!$D$1', null, 1)
    ];
    
    $xAxisTickValues = [
        new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String', 'Rendimiento Técnicos!$A$2:$A$'.($row-1), null, $row-2)
    ];
    
    $dataSeriesValues = [
        new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', 'Rendimiento Técnicos!$C$2:$C$'.($row-1), null, $row-2),
        new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number', 'Rendimiento Técnicos!$D$2:$D$'.($row-1), null, $row-2)
    ];

    $series = new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(
        \PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,
        \PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_STANDARD,
        range(0, count($dataSeriesValues) - 1),
        $dataSeriesLabels,
        $xAxisTickValues,
        $dataSeriesValues
    );

    $plot = new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null, [$series]);
    $legend = new \PhpOffice\PhpSpreadsheet\Chart\Legend(
        \PhpOffice\PhpSpreadsheet\Chart\Legend::POSITION_RIGHT,
        null,
        false
    );
    $title = new \PhpOffice\PhpSpreadsheet\Chart\Title('Rendimiento por Técnico');

    $chart = new \PhpOffice\PhpSpreadsheet\Chart\Chart(
        'rendimiento',
        $title,
        $legend,
        $plot
    );

    $chart->setTopLeftPosition('J2');
    $chart->setBottomRightPosition('P15');

    $sheet->addChart($chart);

    // Autoajustar columnas
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Agregar filtros
    $sheet->setAutoFilter('A1:H' . ($row-1));

    // Guardar el archivo
    $writer = new Xlsx($spreadsheet);
    $writer->setIncludeCharts(true);
    $writer->save('php://output');

} catch (Exception $e) {
    error_log("Error en exportación: " . $e->getMessage());
    header('Location: ../error.php?message=' . urlencode('Error al exportar el reporte'));
}

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');