@extends('layouts/layoutMaster')

@section('title', 'Change Password')

@section('content')
   <div class="container-xxl flex-grow-1 container-p-y">
      <h4 class="fw-bold py-3 mb-4">
         <span class="text-muted fw-light">Account Settings /</span> Change Password
      </h4>

      @if (session('success'))
         <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
      @endif

      <div class="row">
         <div class="col-md-12">
            <div class="card mb-4">
               <h5 class="card-header">Change Password</h5>
               <div class="card-body">
                  <form id="formChangePassword" method="POST" action="{{ route('profile.password.update') }}">
                     @csrf
                     @method('PUT')
                     <div class="row">
                        <div class="mb-4 col-md-6 form-password-toggle">
                           <div class="input-group input-group-merge">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control @error('current_password') is-invalid @enderror"
                                    type="password" name="current_password" id="current_password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    required />
                                 <label for="current_password">Current Password</label>
                              </div>
                              <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                           </div>
                           @error('current_password')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                     </div>
                     <div class="row">
                        <div class="mb-4 col-md-6 form-password-toggle">
                           <div class="input-group input-group-merge">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control @error('password') is-invalid @enderror" type="password"
                                    id="password" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    required />
                                 <label for="password">New Password</label>
                              </div>
                              <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                           </div>
                           @error('password')
                              <div class="text-danger small mt-1">{{ $message }}</div>
                           @enderror
                        </div>
                        <div class="mb-4 col-md-6 form-password-toggle">
                           <div class="input-group input-group-merge">
                              <div class="form-floating form-floating-outline">
                                 <input class="form-control" type="password" name="password_confirmation"
                                    id="password_confirmation"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    required />
                                 <label for="password_confirmation">Confirm New Password</label>
                              </div>
                              <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line"></i></span>
                           </div>
                        </div>
                        <div>
                           <button type="submit" class="btn btn-primary me-2">Update Password</button>
                        </div>
                     </div>
                  </form>
               </div>
            </div>

            <div class="card">
               <h5 class="card-header">Password Requirements</h5>
               <div class="card-body">
                  <ul class="ps-4 mb-0">
                     <li class="mb-1">Minimum 8 characters long - the more, the better</li>
                     <li>At least one number, symbol, or special character</li>
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
@endsection
