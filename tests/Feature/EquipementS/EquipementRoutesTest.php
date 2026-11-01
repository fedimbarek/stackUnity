<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EquipementRoutesTest extends TestCase
{
    public function test_routes_equipements_exist(): void
    {
        $routes = [
            'front.equipements',
            'front.equipements.reservations.store',
            'admin.equipements.index',
            'admin.equipements.store',
            'admin.equipements.update',
            'admin.equipements.destroy',
        ];

        foreach ($routes as $route) {
            $this->assertTrue(
                Route::has($route),
                "Route manquante : {$route}"
            );
        }
    }

    public function test_routes_reservations_exist(): void
    {
        $routes = [
            'admin.equipements.reservations.index',
            'admin.equipements.reservations.confirm',
            'admin.equipements.reservations.destroy',
        ];

        foreach ($routes as $route) {
            $this->assertTrue(
                Route::has($route),
                "Route manquante : {$route}"
            );
        }
    }
}
