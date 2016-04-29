<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class AlertsController extends Controller
{
  /**
   *
   */
  public function index()
  {
    $stats = [
      'mbp-user-import: MBP_UserCSVfileTools: gatherIMAP attachment: Niche',
      'mbp-user-import: MBP_UserCSVfileTools: gatherIMAP attachment: AfterSchool'
    ];
    return view('alerts.index')->with('stats', $stats);
  }
}
