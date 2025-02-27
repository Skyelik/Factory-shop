<?php

class Database
{
    private $connection;

    public function __construct($config)
    {
        try {
            $this->connection = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                $config['user'],
                $config['password']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
