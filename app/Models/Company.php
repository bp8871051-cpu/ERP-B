<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'email', 'phone', 'address', 'currency', 'currency_symbol', 'tax_number', 'logo', 'status'
    ];

    public function departments() { return $this->hasMany(Department::class); }
    public function users() { return $this->hasMany(User::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function salesOrders() { return $this->hasMany(SalesOrder::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }
}
