<?php

namespace App\Repositories\LawRepository;

interface ILawRepository {
    public function index($encyclopediaId, $page=1, $fields = ['id', 'law_number', 'law_text', 'encyclopedia_id'], $resultPerPage=10);

    public function find(int $id, $fields = ['id', 'law_number', 'law_text', 'encyclopedia_id']);

    public function search(int $encyclopediaId, string $search, int $page=1, $field='all', $resultPerPage=10);
}
