<?php
declare(strict_types=1);

namespace Attlaz\Framework\App\Logger;

class HtmlFormatter extends \Monolog\Formatter\HtmlFormatter
{
    private $showContext = false;

    private $showExtra = false;

    public function format(array $record)
    {
//        $output = $this->addTitle($record['level_name'], $record['level']);

        $level = $record['level'];
        $output = '<table cellspacing="1" width="100%" class="monolog-output" style="border-left:5px solid ' . $this->logLevels[$level] . ';margin-bottom:5px">';

        $output .= '<tr>';
        $output .= '<td style="width: 100px;padding-left: 5px">' . $record['level_name'] . '</td>';
        $output .= '<td>' . (string)$record['message'] . '</td>';
        $output .= '<td style="width: 114px;font-size: 11px;text-align: right;">' . $record['datetime']->format($this->dateFormat) . '</td>';
        $output .= '</tr>';

        if ($this->showContext && $record['context']) {
            $embeddedTable = '<tr >';
            foreach ($record['context'] as $key => $value) {
                $embeddedTable .= $td = '<pre>' . $key . ': ' . htmlspecialchars($this->convertToString($value), ENT_NOQUOTES, 'UTF-8') . '</pre>';
            }
            $embeddedTable .= '</tr>';
            $output .= $embeddedTable;//$this->addRow('Context', $embeddedTable, false);
        }
        if ($this->showExtra && $record['extra']) {
            $embeddedTable = '<tr>';
            foreach ($record['extra'] as $key => $value) {
                $embeddedTable .= $td = '<pre>' . $key . ': ' . htmlspecialchars($this->convertToString($value), ENT_NOQUOTES, 'UTF-8') . '</pre>';
            }
            $embeddedTable .= '</tr>';
            $output .= $embeddedTable;//$this->addRow('Extra', $embeddedTable, false);
        }

        return $output . '</table>';
    }

    public function setShowContext(bool $showContext)
    {
        $this->showContext = $showContext;
    }

    public function setShowExtra(bool $showExtra)
    {
        $this->showExtra = $showExtra;
    }
}