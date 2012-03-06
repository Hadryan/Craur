<?php

$package_name = 'craur';
$ignore_files = array('bootstrap_for_test.php');
$source_file = $argv[1];
$target_file = $argv[2];

$full_report = array();

foreach (explode(PHP_EOL, file_get_contents($source_file)) as $raw_line)
{
    if (empty($raw_line))
    {
        continue;
    }
    $line = json_decode($raw_line, true);
    foreach ($line as $coverage_file => $coverage_data)
    {
        if (in_array(basename($coverage_file), $ignore_files))
        {
            continue ;
        }
        if (!isset($full_report[$coverage_file]))
        {
            $full_report[$coverage_file] = array();
        }
        foreach ($coverage_data as $line => $count)
        {
            if (isset($full_report[$coverage_file][$line]))
            {
                $full_report[$coverage_file][$line] = max($full_report[$coverage_file][$line], $count);
            }
            else
            {
                $full_report[$coverage_file][$line] = $count;
            }
        }
    }
}

$clover_xml = array(
    'coverage' => array(
        '@clover' => '2.5.0',
        'project' => array(
            'metrics' => array(
                '@packages' => 1,
                '@coveredelements' => 0,
                '@elements' => 0,
                '@coveredstatements' => 0,
                '@statements' => 0,
            ),
            'package' => array(
                '@name' => $package_name,
                'metrics' => array(),
                'file' => array()
            )
        )
    )
);

foreach ($full_report as $coverage_file => $coverage_data)
{
    $clover_xml_entry = array(
        '@path' => $coverage_file,
        '@name' => basename($coverage_file),
        'metrics' => array(
            '@elements' => 0,
            '@statements' => 0,
        ),
        'line' => array()
    );
    $covered_statements = 0;
    $total_statements = 0;
    foreach ($coverage_data as $line => $count)
    {
        if ($count > -2)
        {
            if ($count > 0)
            {
                $covered_statements++;
            }
            $total_statements++;
            
            $clover_xml_entry['line'][] = array(
                '@num' => $line, '@count' => $count, '@type' => 'stmt'
            );
        }
    }

    $clover_xml_entry['metrics']['@coveredelements'] = $covered_statements;
    $clover_xml_entry['metrics']['@coveredstatements'] = $covered_statements;
    $clover_xml_entry['metrics']['@elements'] = $total_statements;
    $clover_xml_entry['metrics']['@statements'] = $total_statements;
    $clover_xml['coverage']['project']['package']['file'][] = $clover_xml_entry;
    
    $clover_xml['coverage']['project']['metrics']['@elements'] += $total_statements;
    $clover_xml['coverage']['project']['metrics']['@statements'] += $total_statements;
    $clover_xml['coverage']['project']['metrics']['@coveredelements'] += $covered_statements;
    $clover_xml['coverage']['project']['metrics']['@coveredstatements'] += $covered_statements;
}

$clover_xml['coverage']['project']['package']['metrics']['@elements'] = $clover_xml['coverage']['project']['metrics']['@elements'];
$clover_xml['coverage']['project']['package']['metrics']['@coveredelements'] = $clover_xml['coverage']['project']['metrics']['@coveredelements'];
$clover_xml['coverage']['project']['package']['metrics']['@statements'] = $clover_xml['coverage']['project']['metrics']['@statements'];
$clover_xml['coverage']['project']['package']['metrics']['@coveredstatements'] = $clover_xml['coverage']['project']['metrics']['@coveredstatements'];

require_once(dirname(__FILE__) . '/Craur.class.php');
$report_node = new Craur($clover_xml);
file_put_contents($target_file, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . $report_node->toXmlString());
