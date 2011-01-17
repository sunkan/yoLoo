<?php
namespace yoLoo\Pager;

interface IPager
{
    public function setInfo(array $data);
    public function getInfo();

    public function render();
    public function __toString();

    public function isLastPage();
    public function isFirstPage();
    public function isCurrentPage($page);

    public function getRange();

    public function getFirstPage();
    public function getLastPage();

    public function getNextPage();
    public function getPreviusPage();

    public function getNrOfPages();
}