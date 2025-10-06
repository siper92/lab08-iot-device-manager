<?php

return [
    "page_limit" => env("APP_PAGE_LIMIT", Pagination::DEFAULT_PER_PAGE),
    "use_kafka_for_measurements" => true,
];

class ApplicationConstants
{
    public const KAFKA_MEASUREMENTS_TOPIC = 'lab08_measurements';
}

class Pagination
{
    public const DEFAULT_PER_PAGE = 15;
    public const MAX_PER_PAGE = 100;
    public const PAGE_LIMIT_KEY = 'per_page';
}
