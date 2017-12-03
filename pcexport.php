<?php 
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use DB;
use Input;
use Session; 
use Redirect;
use Excel;

class ReportController extends Controller
{
	// construct ++++++++++++++++++++++++++++++++++++
    public function __construct() { 
      $this->model = new \App\Report;
    }
    public function ReportExportPcDaily($customer_id,$sale_date){
        $TempSaleDate = substr($sale_date,0,10);
        $saledate = $TempSaleDate;
        $pcs = DB::table('pc')->where('customer_id','=',$customer_id)->first();
        $SaleDatas = DB::table('reportsale')->where('customer_id','=',$customer_id)
                                            ->where('sale_date','like','%'.$TempSaleDate.'%')
                                            ->orderBy('product_id', 'asc')
                                            ->get();
        $products = DB::table('product')->select('product_id','product_name')->get();
        $promotions = DB::table('promotion')->get();
        $total = 0;
        $totalamount = 0;
        $totalnet = 0;
        foreach ($SaleDatas as $value) {
                $total = $total+$value->sale_price;
                $totalamount = $totalamount+$value->value;
                $totalnet = $totalnet+$value->sale_net_price;
        }
        $date = substr($TempSaleDate,8,2);
        $month = substr($TempSaleDate,5,2);
        $year = substr($TempSaleDate,0,4);
        $datereport=$date."/".$month."/".$year;

      $data = array(
        array('รหัสสินค้า','ชื่อสินค้า','สี','ขนาด','จำนวน','หน่วย','ราคา/หน่วย','ราคารวม','ราคาสุทธิ','โปรโมชั่น')
      );
      foreach ($SaleDatas as $index => $SaleData) {
        $data[$index+1][0] = $SaleData->product_id;
        foreach($products as $product ) {
          if ($product->product_id == $SaleData->product_id){
              $data[$index+1][1] = $product->product_name;
          }
        } 
        $data[$index+1][2] = $SaleData->color;
        $data[$index+1][3] = $SaleData->size;
        $data[$index+1][4] = $SaleData->value;
        $data[$index+1][5] = $SaleData->unit;
        $data[$index+1][6] = $SaleData->sale_price/$SaleData->value;
        $data[$index+1][7] = $SaleData->sale_price;
        $data[$index+1][8] = $SaleData->sale_net_price;
        foreach($promotions as $promotion){
          if ($promotion->promotion_id == $SaleData->promotion_id){
              $data[$index+1][9] = $promotion->promotion_name;
          }
        }
      }
      $Startformate=date_create($TempSaleDate);
      $name=$name="รายงานยอดขายของ ".$pcs->first_name." ".$pcs->last_name." วันที่ ".date_format($Startformate,"d/m/Y");
      Excel::create($name, function($excel) use($data) {
        $excel->sheet("SaleInDailyList", function($sheet) use($data) {
            $sheet->cells('A1:J1', function($cells) {
              $cells->setBackground('#FFFF2A');
              $cells->setFont(array(
                'size'       => '11',
                'bold'       =>  true
              ));

            });
            $sheet->setWidth(array(
              'A'     =>  15,
              'B'     =>  50,
              'C'     =>  10,
              'D'     =>  15,
              'E'     =>  15,
              'F'     =>  15,
              'G'     =>  15,
              'H'     =>  15,
              'I'     =>  15,
              'J'     =>  20,
            ));
            $sheet->setColumnFormat(array(
              'E' => '0',
              'G' => '#,##0.00',
              'H' => '#,##0.00',
              'I' => '#,##0.00',
            )); 
            $sheet->fromArray($data, null, 'A1', false, false);
        });
      })->export('xls');
      return;
    }
 }
