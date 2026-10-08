@extends('layouts.app')

@section('content')
<div class="row h-100">
    <!-- Left Side: Menu Items (65%) -->
    <div class="col-md-8 d-flex flex-column h-100">
        <!-- Dynamic Categories Filter -->
        <div class="mb-3" id="category-filters">
            <button class="btn btn-dark me-2 mb-2 category-btn active" data-category="all">All</button>
            @foreach ($categories as $category)
                <button class="btn btn-outline-dark me-2 mb-2 category-btn" data-category="{{ $category->categoryID }}">
                    {{ $category->categoryname }}
                </button>
            @endforeach
        </div>

        <!-- Menu Grid -->
        <div class="row overflow-auto flex-grow-1" id="menu-grid">
            @foreach ($menuItems as $item)
                <div class="col-sm-4 col-md-3 mb-3 menu-card-wrapper" data-category="{{ $item->categoryID }}">
                    <div class="card h-100 shadow-sm item-card" style="cursor: pointer;" 
                         data-id="{{ $item->menuID }}" 
                         data-name="{{ $item->itemname }}" 
                         data-price="{{ $item->price }}">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <h6 class="card-title fw-bold text-brown">{{ $item->itemname }}</h6>
                            <p class="card-text text-muted mb-0">₱{{ number_format($item->price, 2) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Right Side: Order Details / Cart (35%) -->
    <div class="col-md-4 d-flex flex-column bg-white shadow-sm rounded p-3 h-100 border-start">
        <h4 class="fw-bold text-brown mb-3 border-bottom pb-2">Current Order</h4>
        <div class="flex-grow-1 overflow-auto">
            <table class="table table-borderless table-sm align-middle">
                <tbody id="cart-table-body"></tbody>
            </table>
        </div>
        <div class="mt-auto border-top pt-3">
            <div class="d-flex justify-content-between mb-3 px-2">
                <h5 class="fw-bold mb-0">Total:</h5>
                <h5 class="fw-bold text-success mb-0" id="total-amount-display">₱0.00</h5>
            </div>
            <button id="checkout-btn" class="btn btn-success w-100 py-3 fw-bold fs-5 shadow-sm" disabled>
                CHECKOUT
            </button>
        </div>
    </div>
</div>
@endsection

