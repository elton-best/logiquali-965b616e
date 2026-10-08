<?php

namespace App\Utils;

class CsvExporter
{
    /**
     * Exporter des données en CSV
     * 
     * @param array $data Tableau de données (array of arrays)
     * @param array $headers Entêtes des colonnes
     * @param string|null $filename Nom du fichier (null = retourne le contenu)
     * @return string|void
     */
    public static function export(array $data, array $headers, ?string $filename = null)
    {
        $output = fopen('php://temp', 'r+');
        
        // Écrire les entêtes
        fputcsv($output, $headers, ';');
        
        // Écrire les données
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Si filename fourni, télécharger
        if ($filename) {
            foreach (ExportHeaders::attachmentHeaders($filename, 'text/csv; charset=utf-8') as $k => $v) {
                header($k . ': ' . $v);
            }
            echo "\xEF\xBB\xBF"; // UTF-8 BOM pour Excel
            echo $csv;
            exit;
        }
        
        return $csv;
    }

    /**
     * Exporter une collection Eloquent en CSV
     *
     * @param iterable|\Illuminate\Support\Collection $collection Collection or array of models/arrays
     * @param array $columns Mapping of keys to headers
     * @param string|null $filename If provided, forces download
     * @return string|void
     */
    public static function exportCollection($collection, array $columns, ?string $filename = null)
    {
        $headers = array_values($columns);
        $data = [];
        
        foreach ($collection as $item) {
            $row = [];
            foreach (array_keys($columns) as $key) {
                $row[] = data_get($item, $key, '');
            }
            $data[] = $row;
        }
        
        return self::export($data, $headers, $filename);
    }

    /**
     * Exemple d'utilisation pour Non-Conformités
     */
    public static function exportNonConformities($nonConformities, string $filename = 'non-conformites.csv')
    {
        $columns = [
            'ref' => 'Référence',
            'description' => 'Description',
            'severity' => 'Gravité',
            'source' => 'Source',
            'detected_at' => 'Date détection',
            'workflow_state.name' => 'Statut',
            'responsible.name' => 'Responsable'
        ];
        
        return self::exportCollection($nonConformities, $columns, $filename);
    }

    /**
     * Exemple d'utilisation pour Audits
     */
    public static function exportAudits($audits, string $filename = 'audits.csv')
    {
        $columns = [
            'ref' => 'Référence',
            'title' => 'Titre',
            'type' => 'Type',
            'planned_date' => 'Date prévue',
            'actual_date' => 'Date réalisée',
            'lead_auditor.name' => 'Auditeur principal',
            'workflow_state.name' => 'Statut'
        ];
        
        return self::exportCollection($audits, $columns, $filename);
    }
}
