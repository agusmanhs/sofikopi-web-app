@php
   $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Akun Belum Terdaftar')

@section('content')
   <div class="container-xxl container-p-y">
      <div class="misc-wrapper text-center">
         <div class="mb-4">
            <h2 class="mb-2 mx-2">Data Pegawai Tidak Ditemukan 🕵🏻‍♀️</h2>
            <p class="mb-4 mx-2">Halo <strong>{{ auth()->user()->name }}</strong>, akun Anda aktif tetapi belum terhubung
               dengan data kepegawaian.<br>
               Silakan hubungi Administrator atau HRD untuk melengkapi profil Anda.</p>
            <div class="d-flex justify-content-center gap-2">
               <a href="javascript:void(0);"
                  onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-primary">
                  <i class="ri-logout-box-r-line me-1"></i> Logout
               </a>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
               @csrf
            </form>
         </div>
         <div class="mt-4">
            <img src="{{ asset('assets/img/illustrations/girl-doing-yoga-light.png') }}" alt="page-misc-error-light"
               width="500" class="img-fluid" data-app-dark-img="illustrations/girl-doing-yoga-dark.png"
               data-app-light-img="illustrations/girl-doing-yoga-light.png">
         </div>
      </div>
   </div>
@endsection
