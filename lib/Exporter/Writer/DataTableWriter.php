<?php

namespace Exporter\Writer;

/**
 * Class DataTableWriter
 *
 * This class is based on datatables 1.9 docs -> http://legacy.datatables.net/usage/
 *
 * @package Exporter\Writer
 */
class DataTableWriter implements WriterInterface
{
    protected $pagingType;
    protected $tableElementId;
    protected $jsTableVarName;
    protected $no_records_found_text;

    public $displayLength = 50;

    protected $showSearchField = false;
    protected $filter = false;

    /**
     * @return void
     */
    public function open()
    {
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function write(array $data)
    {
    }

    /**
     * @return void
     */
    public function close()
    {
    }


    public function __construct($tableElementId, $jsTableVarName, $displayLength = null, $no_records_found_text = "Geen records gevonden", $paging_description = "records")
    {
        $this->tableElementId = $tableElementId;
        $this->jsTableVarName = $jsTableVarName;
        $this->no_records_found_text = $no_records_found_text;
        $this->paging_description = $paging_description;
        if ($displayLength != null)
            $this->displayLength = $displayLength;
    }

    public function enableFullPaging()
    {
        $this->pagingType = "full_numbers";
    }

    public function output($data = null, $columns = null)
    {
        $html = '      var ' . $this->jsTableVarName . ' = $("#' . $this->tableElementId . '").dataTable({' . "\n";
        $html .= '          "bStateSave": true' . ", \n";
        $html .= '          "aaData": ' . json_encode($data) . ", \n";
        $html .= '          "aoColumns": ' . json_encode($columns) . ", \n";
        $html .= '          "iDisplayLength"    : ' . $this->displayLength . ", \n";
        $html .= '          "bFilter": ' . $this->getFilterValue() . ", \n";
        $html .= '          "bInfo": true' . ", \n";
//        $html .= '          "bLengthChange": false' . ", \n";
        $html .= '          "aLengthMenu": [[25, 50, 100, 200, -1], [25, 50, 100, 200, "All"]]' . ", \n";
        $html .= '          "sDom": ' . $this->generateDomValue() . ", \n";
        if ($this->pagingType) {
            $html .= '          "sPaginationType": "' . $this->pagingType . '"' . ", \n";
        }
        $html .= '          "oLanguage": {' . "\n";
        $html .= '              "sInfo" : "_START_ tot _END_ van de _TOTAL_ ' . $this->paging_description . '"' . ",\n";
        $html .= '              "sLengthMenu" : "Toon _MENU_ regels"' . ",\n";
        $html .= '              "sInfoEmpty" : "Geen records gevonden"' . ",\n";
        $html .= '              "sInfoFiltered" : " - gefilterd van _MAX_ resultaten"' . ",\n";
        $html .= '              "sSearch" : "Zoek"' . ",\n";
        $html .= '              "sZeroRecords" : "' . $this->no_records_found_text . '"' . ",\n";
        $html .= '              "oPaginate": {' . "\n";
        $html .= '                  "sPrevious": "Vorige"' . ",\n";
        $html .= '                  "sNext": "Volgende"' . ",\n";
        $html .= '              }' . "\n";
        $html .= '          }' . " \n";
        $html .= '      });' . "\n";

        return $html;
    }

    public function render($source, array $columns)
    {

        $data = array();
        foreach ($source as $r) {
            $row = array();
            foreach ($columns as $column) {
                if (is_array($column))
                    if (array_key_exists('type', $column) && $column['type'] == 'date')
                        $row[] = date($column['format'], strtotime($r[$column[0]]));
                    elseif (array_key_exists('type', $column) && $column['type'] == 'time')
                        $row[] = (!empty($r[$column[0]])) ? date($column['format'], strtotime($r[$column[0]])) : $r[$column[0]];
                    else
                        $row[] = $r[$column[0]];
                else
                    $row[] = $r[$column];
            }
            $data[] = $row;
        }

        $cols = array();
        foreach ($columns as $column) {
            $col = array();

            // set title
            if (is_array($column) && array_key_exists('title', $column))
                $col['sTitle'] = $column['title'];
            elseif (is_array($column))
                $col['sTitle'] = $column[0];
            else
                $col['sTitle'] = $column;

            // type for dd/mm/yyyy dates
            if (is_array($column) && array_key_exists('type', $column)) {
                if ($column['type'] == 'date')
                    $col['sType'] = 'date-euro';
            }

            // add width
            if (is_array($column) && array_key_exists('width', $column)) {
                $col['sWidth'] = $column['width'];
            }

            $cols[] = $col;
        }

        return $this->output($data, $cols);
    }

    /**
     * @author Didier <didier@e-golf4u.nl>
     *
     * @param boolean $value
     */
    public function showSearchField($value)
    {
        $this->showSearchField = $value;
        $this->filter = $value;
    }


    private function generateDomValue()
    {
        $sDom = '\'<"top"l';

        if ($this->showSearchField) $sDom .= 'f';

        $sDom .= '><"pager"ip>t<"clear"> \'';

        return $sDom;
    }

    private function getFilterValue()
    {
        return $this->filter ? 'true' : 'false';
    }

}