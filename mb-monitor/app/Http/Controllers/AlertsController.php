<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Alert;


class AlertsController extends Controller
{
  /**
   *
   */
  public function index()
  {
    $alerts = Alert::all();
    return view('alerts.index')->with('alerts', $alerts);
  }

  /**
   *
   */
  public function show($id) {

    $alert = Alert::find($id);
    return $alert;
  }
}
