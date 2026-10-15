<?php
namespace App\Http\Controllers\Rental;
use App\Http\Controllers\CrudController;
class ReservationController extends CrudController {
    protected string $model = \App\Models\Rental\RentalReservation::class;
    protected string $routePrefix = 'rental.reservations';
    protected string $viewPrefix = 'modules.rental.reservations';
    protected string $title = 'Reservasi Sewa';
    protected string $perm = 'rental';
    protected array $columns = [
        'number' => 'Nomor',
        'status' => 'Status'
    ];
    protected array $fields = [
        ['name'=>'status','label'=>'Status','type'=>'text']
    ];
    protected array $rules = [
        
    ];
    protected array $searchable = ['number'];
    protected ?string $entityField = null;
}
