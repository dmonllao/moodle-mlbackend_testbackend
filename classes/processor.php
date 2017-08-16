<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * test predictions processor
 *
 * @package   mlbackend_testbackend
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mlbackend_testbackend;

defined('MOODLE_INTERNAL') || die();

/**
 * Test predictions processor.
 *
 * @package   mlbackend_testbackend
 * @copyright 2016 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor implements \core_analytics\predictor {

    /**
     * Checks if the processor is ready to use.
     *
     * @return bool
     */
    public function is_ready() {
        return true;
    }

    /**
     * Nothing, just display the dataset contents for debugging.
     *
     * @param string $uniqueid
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function train_classification($uniqueid, \stored_file $dataset, $outputdir) {

        var_dump('TRAINING FILE CONTENTS');
        var_dump($dataset->get_content());

        $resultobj = new \stdClass();
        $resultobj->status = \core_analytics\model::OK;
        $resultobj->info = array();
        return $resultobj;
    }

    /**
     * Returning random predictions based on the target classes list (available in metadata).
     *
     * @param string $uniqueid
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function classify($uniqueid, \stored_file $dataset, $outputdir) {

        var_dump('PREDICTION FILE CONTENTS');
        var_dump($dataset->get_content());

        $fh = $dataset->get_content_file_handle();

        // The first lines are var names and the second one values.
        $metadata = $this->extract_metadata($fh);

        $targetclasses = $this->get_target_classes($metadata['targetclasses']);

        // Skip headers.
        fgets($fh);

        $predictions = array();
        while (($data = fgetcsv($fh)) !== false) {
            $sampledata = array_map('floatval', $data);
            $sampleid = $data[0];

            // Random index determine random class.
            $randindex = rand(0, count($targetclasses) - 1);
            $predictions[] = array($sampleid, $targetclasses[$randindex]);
        }
        fclose($fh);

        $resultobj = new \stdClass();
        $resultobj->status = \core_analytics\model::OK;
        $resultobj->info = array();
        $resultobj->predictions = $predictions;

        return $resultobj;
    }

    /**
     * Does nothing, just display dataset contents for debugging and return ok.
     *
     * @param string $uniqueid
     * @param float $maxdeviation
     * @param int $niterations
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function evaluate_classification($uniqueid, $maxdeviation, $niterations, \stored_file $dataset, $outputdir) {

        var_dump('EVALUATION FILE CONTENTS');
        var_dump($dataset->get_content());

        $resultobj = new \stdClass();
        $resultobj->status = \core_analytics\model::OK;
        $resultobj->score = 1;
        $resultobj->info = array();
        return $resultobj;
    }

    /**
     * Train this processor regression model using the provided supervised learning dataset.
     *
     * @param string $uniqueid
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function train_regression($uniqueid, \stored_file $dataset, $outputdir) {
        return $this->train_classification($uniqueid, $dataset, $outputdir);
    }

    /**
     * Returning random predictions based on the target max and min values (available in metadata).
     *
     * @param string $uniqueid
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function estimate($uniqueid, \stored_file $dataset, $outputdir) {

        var_dump('PREDICTION FILE CONTENTS');
        var_dump($dataset->get_content());

        $fh = $dataset->get_content_file_handle();

        // The first lines are var names and the second one values.
        $metadata = $this->extract_metadata($fh);

        // Skip headers.
        fgets($fh);

        $predictions = array();
        while (($data = fgetcsv($fh)) !== false) {
            $sampledata = array_map('floatval', $data);
            $sampleid = $data[0];

            // Float with 2 decimal values.
            $min = $metadata['targetmin'];
            $max = $metadata['targetmax'];
            $prediction = mt_rand($min * 100, $max * 100) / 100;

            $predictions[] = array($sampleid, $prediction);
        }
        fclose($fh);

        $resultobj = new \stdClass();
        $resultobj->status = \core_analytics\model::OK;
        $resultobj->info = array();
        $resultobj->predictions = $predictions;

        return $resultobj;

    }

    /**
     * Evaluates this processor regression model using the provided supervised learning dataset.
     *
     * @param string $uniqueid
     * @param float $maxdeviation
     * @param int $niterations
     * @param \stored_file $dataset
     * @param string $outputdir
     * @return \stdClass
     */
    public function evaluate_regression($uniqueid, $maxdeviation, $niterations, \stored_file $dataset, $outputdir) {
        return $this->evaluate_classification($uniqueid, $maxdeviation, $niterations, $dataset, $outputdir);
    }

    /**
     * Extracts metadata from the dataset file.
     *
     * The file poiter should be located at the top of the file.
     *
     * @param resource $fh
     * @return array
     */
    protected function extract_metadata($fh) {
        $metadata = fgetcsv($fh);
        return array_combine($metadata, fgetcsv($fh));
    }

    /**
     * Parses a list of classes in this shape [12, 32, 23] removing non-numeric chars.
     *
     * @param string $classesstr
     * @return int[]
     */
    protected function get_target_classes($classesstr) {
        $classeslist = str_replace(array('[', ']'), array('', ''), $classesstr);
        $classes = explode(',', $classeslist);
        array_walk($classes, function(&$value, $key) {
            $value = preg_replace("/[^0-9]/", "", $value);
        });
        return $classes;
    }
}
