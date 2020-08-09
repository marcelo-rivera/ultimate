<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transaction;
use App\Business;
use App\City;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\FilesFolders;
use NFePHP\DA\NFe\Daevento;
use App\Services\NFeService;

class NfeController extends Controller
{
	public function novo($id){


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
			return redirect('/nfe/ver/'.$transaction->id);
		}

		$erros = [];
		if($transaction->contact->cpf_cnpj == null){
			$msg = 'Não é possivel emitir NF-e para cliente sem CNPJ ou CPF';
			array_push($erros, $msg);
		}

		if($business->cnpj == '00.000.000/0000-00'){
			$msg = 'Informe a configuração do emitente';
			array_push($erros, $msg);
		}

		if(sizeof($erros) > 0){
			return view('nfe.erros')
			->with(compact('erros'));
		}

		return view('nfe.novo')
		->with(compact('transaction', 'business'));

	}

	public function renderizarDanfe($id){

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

		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);

		$nfe = $nfe_service->gerarNFe($transaction);
		// print_r($nfe);
		$xml = $nfe['xml'];


		// echo public_path('uploads/business_logos/' . $config->logo);
		try {
			$danfe = new Danfe($xml);
			$id = $danfe->monta();
			$pdf = $danfe->render();
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  
		
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

		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);

		$nfe = $nfe_service->gerarNFe($transaction);
		// print_r($nfe);
		$xml = $nfe['xml'];

		header('Content-Type: application/xml');
		echo $xml;
		// if(!is_dir(public_path('xml_nfe/'.$cnpj))){
		// 	mkdir(public_path('xml_nfe/'.$cnpj), 0777, true);
		// }
		// file_put_contents(public_path('xml_nfe/'.$cnpj.'/teste.xml'), $xml);
	}

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

		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);

		if($transaction->estado == 'REJEITADO' || $transaction->estado == 'NOVO'){
			header('Content-type: text/html; charset=UTF-8');

			$nfe = $nfe_service->gerarNFe($transaction);
			// return response()->json($signed, 200);
			
			$signed = $nfe_service->sign($nfe['xml']);
			// return response()->json($signed, 200);
			$resultado = $nfe_service->transmitir($signed, $nfe['chave'], $cnpj);

			if(!isset($resultado['erro'])){
				$transaction->chave = $nfe['chave'];
				$transaction->numero_nfe = $nfe['nNf'];
				$transaction->estado = 'APROVADO';
				$transaction->save();
				return response()->json($resultado, 200);

			}else{
				$transaction->estado = 'REJEITADO';
				$transaction->save();
				return response()->json($resultado['protocolo'], $resultado['status']);
			}


		}else{
			return response()->json("Esta NF-e já esta aprovada", 200);
		}



		return response()->json($xml, 200);

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

		return view('nfe.ver')
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

		return response()->download(public_path('xml_nfe/'.$cnpj.'/'.$transaction->chave.'.xml'));
	}

	public function baixarXmlCancelado($id){
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

		return response()->download(public_path('xml_nfe_cancelada/'.$cnpj.'/'.$transaction->chave.'.xml'));
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

		$xml = file_get_contents(public_path('xml_nfe/'.$cnpj.'/'.$transaction->chave.'.xml'));

		try {
			$danfe = new Danfe($xml);
			$id = $danfe->monta($logo);
			$pdf = $danfe->render();
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  

	}

	public function imprimirCorrecao($id){
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

		$xml = file_get_contents(public_path('xml_nfe_correcao/'.$cnpj.'/'.$transaction->chave.'.xml'));

		try {
			$dadosEmitente = $this->getEmitente($business);

			$daevento = new Daevento($xml, $dadosEmitente);
			$daevento->debugMode(true);
			$pdf = $daevento->render($logo);
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  

	}

	public function imprimirCancelamento($id){
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

		$xml = file_get_contents(public_path('xml_nfe_cancelada/'.$cnpj.'/'.$transaction->chave.'.xml'));

		try {
			$dadosEmitente = $this->getEmitente($business);

			$daevento = new Daevento($xml, $dadosEmitente);
			$daevento->debugMode(true);
			$pdf = $daevento->render($logo);
			header('Content-Type: application/pdf');
			echo $pdf;
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}  

	}

	private function getEmitente($config){

		return [
			'razao' => $config->razao_social,
			'logradouro' => $config->rua,
			'numero' => $config->numero,
			'complemento' => '',
			'bairro' => $config->bairro,
			'CEP' => $config->cep,
			'municipio' => $config->cidade->nome,
			'UF' => $config->cidade->uf,
			'telefone' => '',
			'email' => ''
		];
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


		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);


		$nfe = $nfe_service->cancelar($transaction, $request->justificativa, $cnpj);
		if(!isset($nfe['erro'])){

			$transaction->estado = 'CANCELADO';
			$transaction->save();
			return response()->json($nfe, 200);


		}else{
			return response()->json($nfe, $nfe['status']);
		}
	}

	public function corrigir(Request $request){

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


		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);


		$nfe = $nfe_service->cartaCorrecao($transaction, $request->justificativa, $cnpj);
		if(!isset($nfe['erro'])){
			return response()->json($nfe, 200);

		}else{
			return response()->json($nfe, $nfe['status']);
		}
		
		
	}

	public function lista(){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$business_id = request()->session()->get('user.business_id');
		$notas = Transaction::where('business_id', $business_id)
		->where('numero_nfe', '>', 0)
		->orderBy('id', 'desc')
		->get();

		return view('nfe.lista')
		->with(compact('notas', 'business'));
	}

	public function filtro(Request $request){
		if (!auth()->user()->can('user.create')) {
			abort(403, 'Unauthorized action.');
		}

		$data_inicio = $request->data_inicio;
		$data_final = $request->data_final;

		$data_inicio_convert =  \Carbon\Carbon::parse($data_inicio)->format('Y-m-d');
		$data_final_convert =  \Carbon\Carbon::parse($data_final)->format('Y-m-d');

		$business_id = request()->session()->get('user.business_id');
		$notas = Transaction::where('business_id', $business_id)
		->whereBetween('created_at', [
			$data_inicio_convert, 
			$data_final_convert])
		->where('numero_nfe', '>', 0)
		->orderBy('id', 'desc')
		->get();

		$business = Business::find($business_id);
		$cnpj = str_replace(".", "", $business->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);

		$zip_file = public_path('xml_nfe/'.$cnpj.'/'.'xml.zip');
		$zip = new \ZipArchive();
		$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		foreach($notas as $n){
			if($n->estado == 'APROVADO'){

				if(file_exists(public_path('xml_nfe/'.$cnpj.'/'.$n->chave.'.xml'))){
					$zip->addFile(public_path('xml_nfe/'.$cnpj.'/'.$n->chave.'.xml'), $n->chave . '.xml');
				}
				
			}
		}

		$zip->close();

		$zip_file = public_path('xml_nfe_cancelada/'.$cnpj.'/'.'xml_cancelado.zip');
		$zip = new \ZipArchive();
		$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

		foreach($notas as $n){
			if($n->estado == 'CANCELADO'){

				if(file_exists(public_path('xml_nfe_cancelada/'.$cnpj.'/'.$n->chave.'.xml'))){
					$zip->addFile(public_path('xml_nfe_cancelada/'.$cnpj.'/'.$n->chave.'.xml'), $n->chave . '.xml');
				}
				
			}
		}

		$zip->close();

		return view('nfe.lista')
		->with(compact('notas', 'business', 'data_inicio', 'data_final'));
	}

	public function baixarZipXmlAprovado(){
		$business_id = request()->session()->get('user.business_id');
		$business = Business::find($business_id);
		$cnpj = str_replace(".", "", $business->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);
		return response()->download(public_path('xml_nfe/'.$cnpj.'/'.'xml.zip'));
	}

	public function baixarZipXmlReprovado(){
		$business_id = request()->session()->get('user.business_id');
		$business = Business::find($business_id);
		$cnpj = str_replace(".", "", $business->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);
		return response()->download(public_path('xml_nfe_cancelada/'.$cnpj.'/'.'xml_cancelado.zip'));
	}

	public function consultaCadastro(Request $request){

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


		$nfe_service = new NFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->cidade->uf,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => getenv('CSC'),
			"CSCid" => getenv('CSCid')
		]);

		$cnpj = str_replace(".", "", $request->cnpj);
		$cnpj = str_replace("/", "", $cnpj);
		$cnpj = str_replace("-", "", $cnpj);
		$cnpj = str_replace(" ", "", $cnpj);
		$uf = $request->uf;

		$nfe_service->consultaCadastro($cnpj, $uf);

	}

	public function findCidade(Request $request){
		$cidade = City::
        where('nome', $request->nome)
        ->first();

		return response()->json($cidade);
	}

}
