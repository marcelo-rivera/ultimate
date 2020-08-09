<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\Business;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\DA\NFe\Daevento;
use App\Services\NFCeService;

class NfceController extends Controller
{
    
    public function transmtir(Request $request){

		if (!auth()->user()->can('user.create')) {
			return response()->json('erro', 401);
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $request->id)
		->first();

		if(!$transaction){
			return response()->json('erro', 403);
		}

		$config = Business::find($business_id);


		$cnpj = str_replace(".", "", $config->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		$ncfe_service = new NFCeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		]);

		if($transaction->estado == 'REJEITADO' || $transaction->estado == 'NOVO'){
			header('Content-type: text/html; charset=UTF-8');

			$nfe = $ncfe_service->gerarNFCe($transaction);
			// return response()->json($signed, 200);
			
			$signed = $ncfe_service->sign($nfe['xml']);
			// return response()->json($signed, 200);
			$resultado = $ncfe_service->transmitir($signed, $nfe['chave'], $cnpj);

			if(!isset($resultado['erro'])){
				$transaction->chave = $nfe['chave'];
				$transaction->numero_nfce = $nfe['nNf'];
				$transaction->estado = 'APROVADO';
				$transaction->save();
				return response()->json($resultado, 200);

			}else{
				$transaction->estado = 'REJEITADO';
				$transaction->save();
				return response()->json($resultado['protocolo'], $resultado['status']);
			}

		}else{
			return response()->json("Esta NFC-e já esta aprovada", 200);
		}

		return response()->json($xml, 200);

	}

	public function gerar($id){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		$business = Business::find($business_id);

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		if($transaction->estado != 'NOVO'){
			// return redirect('/nfce/ver/'.$transaction->id);
		}

		$erros = [];

		if($business->cnpj == '00.000.000/0000-00'){
			$msg = 'Informe a configuração do emitente';
			array_push($erros, $msg);
		}

		if(sizeof($erros) > 0){
			return view('nfe.erros')
			->with(compact('erros'));
		}

		return view('nfce.gerar')
		->with(compact('transaction', 'business'));
	}

	public function gerarXml($id){

		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		$config = Business::find($business_id);

		$cnpj = str_replace(".", "", $config->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		$nfce_service = new NFCeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		]);

		$nfe = $nfce_service->gerarNFCe($transaction);

		$xml = $nfe['xml'];

		header('Content-Type: application/xml');
		echo $xml;
		// if(!is_dir(public_path('xml_nfe/'.$cnpj))){
		// 	mkdir(public_path('xml_nfe/'.$cnpj), 0777, true);
		// }
		// file_put_contents(public_path('xml_nfe/'.$cnpj.'/teste.xml'), $xml);
	}

	public function renderizarDanfce($id){

		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		$config = Business::find($business_id);

		$cnpj = str_replace(".", "", $config->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		$nfce_service = new NFCeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		]);

		$nfe = $nfce_service->gerarNFCe($transaction);
		// print_r($nfe);
		$xml = $nfe['xml'];


		// echo public_path('uploads/business_logos/' . $config->logo);
		try {
			$danfe = new Danfce($xml);
			$id = $danfe->monta();
			$pdf = $danfe->render();
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  
		
	}

	public function imprimir($id){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		$business = Business::find($business_id);
		$cnpj = str_replace(".", "", $business->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		$logo = '';
		if($business->logo){
			$logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(
				public_path('uploads/business_logos/' . $business->logo)));
		}

		$xml = file_get_contents(public_path('xml_nfce/'.$cnpj.'/'.$transaction->chave.'.xml'));

		try {
			$danfe = new Danfce($xml);
			$id = $danfe->monta($logo);
			$pdf = $danfe->render();
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  

	}

	public function ver($id){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		$business = Business::find($business_id);

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		return view('nfce.ver')
		->with(compact('transaction', 'business'));
	}

	public function baixarXml($id){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $id)
		->first();

		$business = Business::find($business_id);
		$cnpj = str_replace(".", "", $business->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		if(!$transaction){
			abort(403, 'Unauthorized action.');
		}

		return response()->download(public_path('xml_nfce/'.$cnpj.'/'.$transaction->chave.'.xml'));
	}

	public function cancelar(Request $request){

		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$transaction = Transaction::where('business_id', $business_id)
		->where('id', $request->id)
		->first();

		$config = Business::find($business_id);
		$cnpj = str_replace(".", "", $config->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);


		$nfce_service = new NFCeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		]);


		$nfe = $nfce_service->cancelar($transaction, $request->justificativa, $cnpj);
		if(!isset($nfe['erro'])){

			$transaction->estado = 'CANCELADO';
			$transaction->save();
			return response()->json($nfe, 200);


		}else{
			return response()->json($nfe, $nfe['status']);
		}
	}

}
