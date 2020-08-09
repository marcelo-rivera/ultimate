@extends('layouts.app')
@section('title', __('purchase.add_purchase'))

@section('content')
<!-- Content Header (Page header) -->


<!-- Main content -->
<section class="content">

	{!! Form::open(['url' => '/purchase-xml/save', 'method' => 'post', 'id' => 'add_purchase_form', 'files' => true ]) !!}
	@component('components.widget', ['class' => 'box-primary'])

	@if(count($business_locations) == 1)
	@php 
	$default_location = current(array_keys($business_locations->toArray()));
	$search_disable = false; 
	@endphp
	@else
	@php $default_location = null;
	$search_disable = true;
	@endphp
	@endif
	<div class="col-sm-3">
		<div class="form-group">
			{!! Form::label('location_id', __('purchase.business_location').':*') !!}
			@show_tooltip(__('tooltip.purchase_location'))
			{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
		</div>
	</div>

	<input type="hidden" value="{{json_encode($contact)}}" name="contact">
	<input type="hidden" value="{{json_encode($itens)}}" name="itens">
	<input type="hidden" value="{{json_encode($fatura)}}" name="fatura">
	<input type="hidden" value="{{json_encode($dadosNf)}}" name="dadosNf">

	<div class="row">
		<div class="col-sm-12">
			<div class="form-group">
				<h3 class="box-title">Fornecedor</h3>
				@if($dadosNf['novoFornecedor'])
				<p class="text-danger">*Este é um novo fornecedor, será cadastrado se finalizar a compra!</p>
				@endif
				<div class="row">
					<div class="col-sm-6">

						<span>Nome: <strong>{{$contact['name']}}</strong></span><br>
						<span>CNPJ/CPF: <strong>{{$contact['cpf_cnpj']}}</strong></span><br>
						<span>IE/RG: <strong>{{$contact['ie_rg']}}</strong></span>
					</div>

					<div class="col-sm-6">

						<span>Rua: <strong>{{$contact['rua']}}, {{$contact['numero']}}</strong></span><br>
						<span>Bairro: <strong>{{$contact['bairro']}}</strong></span><br>
						<span>Cidade: <strong>{{$cidade->nome}} ({{$cidade->uf}})</strong></span>

					</div>
				</div>
			</div>
		</div>

		<div class="col-sm-12">
			<div class="form-group">
				<h3 class="box-title">Dados do Documento</h3>

				<div class="row">
					<div class="col-sm-12">

						<span>Chave: <strong>{{$dadosNf['chave']}}</strong></span><br>
						<span>Valor: <strong>{{$dadosNf['vProd']}}</strong></span><br>
						<span>Número: <strong>{{$dadosNf['nNf']}}</strong></span><br>
						<span>Valor do frete: <strong>{{$dadosNf['vFrete']}}</strong></span><br>
						<span>Valor de desconto: <strong>{{$dadosNf['vDesc']}}</strong></span><br>
					</div>

				</div>
			</div>
		</div>

		<div class="col-sm-12">
			<div class="form-group">
				<h3 class="box-title">Produtos</h3>
				<div class="">
					

					<!-- Inicio tabela -->
					<div class="nav-tabs-custom">


						<div class="tab-content">
							<div class="tab-pane active" id="product_list_tab">
								<br><br>
								<div class="table-responsive">
									<div id="product_table_wrapper" class="dataTables_wrapper form-inline dt-bootstrap no-footer">
										<div class="row margin-bottom-20 text-center">
											<table class="table table-bordered table-striped ajax_view hide-footer dataTable no-footer" id="product_table" role="grid" aria-describedby="product_table_info" style="width: 1300px;">
												<thead>
													<tr role="row">
														
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 200px;" aria-label="Produto">Produto</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">Código</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">NCM</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">CFOP</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">Quantidade</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">Valor Unit.</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 100px;" aria-label="Produto">Cod. Barras</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 80px;" aria-label="Produto">Unidade</th>
													</tr>
												</thead>

												<tbody>

													@foreach($itens as $i)

													<tr>
														<td style="width: 200px;">{{$i['xProd']}}</td>
														<td style="width: 80px;">{{$i['codigo']}}</td>
														<td style="width: 80px;">{{$i['NCM']}}</td>
														<td style="width: 80px;">{{$i['CFOP']}}</td>
														<td style="width: 80px;">{{$i['qCom']}}</td>
														<td style="width: 80px;">{{$i['vUnCom']}}</td>
														<td style="width: 100px;">{{$i['codBarras']}}</td>
														<td style="width: 100px;">{{$i['uCom']}}</td>

													</tr>
													@endforeach
													
												</tbody>
											</table>


										</div>

									</div>


								</div>
							</div>
						</div>
					</div>

					<!-- fim tabela -->
				</div>
			</div>
		</div>

		<div class="col-sm-12">
			<div class="form-group">
				<h3 class="box-title">Fatura</h3>
				<div class="">

					<div class="nav-tabs-custom">


						<div class="tab-content">
							<div class="tab-pane active" id="product_list_tab">
								<br><br>
								<div class="table-responsive">
									<div id="product_table_wrapper" class="dataTables_wrapper form-inline dt-bootstrap no-footer">
										<div class="row margin-bottom-20 text-center">
											<table class="table table-bordered table-striped ajax_view hide-footer dataTable no-footer" id="product_table" role="grid" aria-describedby="product_table_info" style="width: 700px;">
												<thead>
													<tr role="row">
														
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 100px;" aria-label="Produto">Número</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 100px;" aria-label="Produto">Vencimento</th>
														<th class="sorting_disabled" rowspan="1" colspan="1" style="width: 100px;" aria-label="Produto">Valor</th>
													</tr>
												</thead>

												<tbody>

													@if(sizeof($fatura) > 0)
													@foreach($fatura as $f)

													<tr>
														<td style="width: 200px;">{{$f['numero']}}</td>
														<td style="width: 80px;">{{$f['vencimento']}}</td>
														<td style="width: 80px;">{{$f['valor_parcela']}}</td>

													</tr>
													@endforeach
													@else
													<tr>
														<td colspan="3">Nenhuma fatura neste XML</td>
													</tr>
													@endif
													
												</tbody>
											</table>


										</div>

									</div>


								</div>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="row">


			<div class="col-sm-12">
				<button type="submit" class="btn btn-primary pull-right btn-flat">Salvar Compra</button>
			</div>
		</div>


	</div>

	@endcomponent
	{!! Form::close() !!}
</section>
<!-- /.content -->

@endsection
