<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter;

use Exporter\Source\SourceIteratorInterface;
use Exporter\Writer\WriterInterface;

class Handler
{
    protected $source;

    protected $writer;

    /**
     * @param Source\SourceIteratorInterface $source
     * @param Writer\WriterInterface         $writer
     */
    public function __construct(SourceIteratorInterface $source, WriterInterface $writer)
    {
        $this->source = $source;
        $this->writer = $writer;
    }

    /**
     */
    public function export()
    {
        $this->writer->open();

        foreach ($this->source as $data) {
            $this->writer->write($data);
        }

        $this->writer->close();
    }

    /**
     * @static
     *
     * @param Source\SourceIteratorInterface $source
     * @param Writer\WriterInterface         $writer
     *
     * @return Handler
     */
    public static function create(SourceIteratorInterface $source, WriterInterface $writer)
    {
        return new self($source, $writer);
    }


    /**
     * @author Didier <didier@e-golf4u.nl>
     *
     * Function for downloading the output file.
     */
    public function download()
    {

        $this->export();

        $file = $this->writer->getFileName();
        $filename = basename($file);
        $size = filesize($file);

        @ob_end_clean(); // turn off output buffering to decrease cpu usage.

        // required for IE
        if ($current_output_compression = ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');

        /* The three lines below basically make the
        download non-cacheable */
        header("Cache-control: no-cache, pre-check=0, post-check=0");
        header("Cache-control: private");
        header('Pragma: private');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        // multipart-download and download resuming support
        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($range) = explode(",", $range, 2);
            list($range, $range_end) = explode("-", $range);
            $range = intval($range);
            if (!$range_end) {
                $range_end = $size - 1;
            } else {
                $range_end = intval($range_end);
            }

            $new_length = $range_end - $range + 1;
            header("HTTP/1.1 206 Partial Content");
            header("Content-Length: $new_length");
            header("Content-Range: bytes $range-$range_end/$size");
        } else {
            $new_length = $size;
            header("Content-Length: " . $size);
        }

        /* output the file itself */
        $chunksize = 1 * (1024 * 1024); //you may want to change this
        $bytes_send = 0;
        if ($file = fopen($file, 'rb')) {
            if (isset($_SERVER['HTTP_RANGE']))
                fseek($file, $range);

            while
            (!feof($file) &&
                (!connection_aborted()) &&
                ($bytes_send < $new_length)) {
                $buffer = fread($file, $chunksize);
                print($buffer); //echo($buffer); // is also possible
                flush();
                $bytes_send += strlen($buffer);
            }
            fclose($file);
        }

        //ini_set('zlib.output_compression', $current_output_compression);

        unlink($this->writer->getFilename());

    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function render(array $columns) {
        return $this->writer->render($this->source, $columns);
    }
}
