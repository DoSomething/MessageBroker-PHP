<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;


class AlertsController extends Controller
{
  /**
   *
   */
  public function index()
  {
    $alerts = DB::table('alerts')->get();
    return view('alerts.index')->with('alerts', $alerts);
  }
}
