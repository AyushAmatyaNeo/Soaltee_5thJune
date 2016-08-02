<?php

namespace Setup\Model;

interface DesignationRepositoryInterface
{
    public function addDesignation(Designation $designation);
    public function editDesignation(Designation $designation);
    public function deleteDesignation(Designation $designation);

    public function fetchAll();

    public function fetchById( $id);
}