<!-- <div class="row">
	<div class="col-md-12">
	    <div class="box box-primary">
				<div class="box-body">

					<div class="form-group row">
		        <div class="col-md-12">
			        <div class="box-body table-responsive">
		            <table class="table table-hover table-bordered table-responsive">
		              <thead style='background-color: #e3e3e3;'>
		                <tr>
											<th width='40%' class='text-left mid'>NO SO</th>
		                  <th width='10%' class='text-left mid'>SHIPMENT</th>
		                  <th width='10%' class='text-right mid bold'>ORDER</th>
		                  <th width='10%' class='text-right mid bold'>PROPOSE</th>
		                  <th width='10%' class='text-right mid bold'>FINISH GOOD</th>
		                  <th width='10%' class='text-right mid bold'>BALANCE FG</th>
		                  <th width='10%' class='text-right mid bold'>PROGRESS</th>
		                </tr>
		              </thead>
		              <tbody>
		                <tr>
		                  <td>
												<select id="sales_order" name="sales_order" class="form-control input-md chosen-select">
													<?php
													foreach (get_sales_order() as $val => $valx) {
														$selx = ($no_so == $valx['no_so']) ? 'selected' : '';
														echo "<option value='" . $valx['no_so'] . "' " . $selx . ">" . $valx['no_so'] . "  [" . strtoupper(date('d-M-Y', strtotime($valx['delivery_date']))) . "] / " . strtoupper($valx['name_customer']) . "</option>";
													}
													?>
												</select>
											</td>
											<td>EXISTING</td>
		                  <td class='text-right mid'><?= number_format($qty_order1); ?></td>
		                  <td class='text-right mid'><?= number_format($qty_propose1); ?></td>
		                  <td class='text-right mid'><?= number_format($qtyfg1); ?></td>
		                  <td class='text-right mid'><?= number_format($qtybal1); ?></td>
		                  <td class='text-right mid'><?= number_format($progres1, 2); ?> %</td>
		                </tr>
		                <tr>
		                  <td>
												<select id="sales_order2" name="sales_order2" class="form-control input-md chosen-select">
													<?php
													foreach (get_sales_order() as $val => $valx) {
														$selx = ($no_so2 == $valx['no_so']) ? 'selected' : '';
														echo "<option value='" . $valx['no_so'] . "' " . $selx . ">" . $valx['no_so'] . "  [" . strtoupper(date('d-M-Y', strtotime($valx['delivery_date']))) . "] / " . strtoupper($valx['name_customer']) . "</option>";
													}
													?>
												</select>
											</d>
											<td>DOHA</td>
		                  <td class='text-right mid'><?= number_format($qty_order2); ?></td>
		                  <td class='text-right mid'><?= number_format($qty_propose2); ?></td>
		                  <td class='text-right mid'><?= number_format($qtyfg2); ?></td>
		                  <td class='text-right mid'><?= number_format($qtybal2); ?></td>
		                  <td class='text-right mid'><?= number_format($progres2, 2); ?> %</td>
		                </tr>
		                <tr>
		                  <td></td>
											<td class='text-left mid'>TOTAL</td>
		                  <td class='text-right mid'><?= number_format($qty_order1 + $qty_order2); ?></td>
		                  <td class='text-right mid'><?= number_format($qty_propose1 + $qty_propose2); ?></td>
		                  <td class='text-right mid'><?= number_format($qtyfg1 + $qtyfg2); ?></td>
		                  <td class='text-right mid'><?= number_format($qtybal1 + $qtybal2); ?></td>
											<?php
											$progres = 0;
											if (($qtyfg1 + $qtyfg2) > 0 and ($qty_propose1 + $qty_propose2) > 0) {
												$progres = (($qtyfg1 + $qtyfg2) / ($qty_propose1 + $qty_propose2)) * 100;
											}
											?>
		                  <td class='text-right mid'><?= number_format($progres, 2); ?> %</td>
		                </tr>
		              </tbody>
		            </table>
			        </div>
		  	    </div>
					</div>
				</div>
      </div>
	</div>
</div> -->
<!-- Dashboard2 -->
<div class="row row-equal-cards">
	<div class="col-md-4">
		<div class="small-box bg-yellow">
			<div class="inner">
				<h3><?= $count_penawaran ?></h3>
				<p>Penawaran Konsultasi Menunggu Approval</p>
			</div>
			<div class="icon">
				<i class="fa fa-file-text-o"></i>
			</div>
			<a href="<?= base_url('approval_penawaran') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
	<div class="col-md-4">
		<div class="small-box bg-orange">
			<div class="inner">
				<h3><?= $count_penawaran_non_kons ?></h3>
				<p>Penawaran Non Konsultasi Menunggu Approval</p>
			</div>
			<div class="icon">
				<i class="fa fa-file-text"></i>
			</div>
			<a href="<?= base_url('approval_penawaran?tab=non_konsultasi') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
	<div class="col-md-4">
		<div class="small-box bg-green">
			<div class="inner">
				<h3><?= $count_spk_sales_konsultan ?></h3>
				<p>SPK Penawaran Menunggu Approval Sales & Konsultan</p>
			</div>
			<div class="icon">
				<i class="fa fa-user"></i>
			</div>
			<a href="<?= base_url('approval_spk_sales_konsultan') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
</div>
<div class="row row-equal-cards">
	<div class="col-md-4">
		<div class="small-box bg-aqua">
			<div class="inner">
				<h3><?= $count_spk_project_leader ?></h3>
				<p>SPK Penawaran Menunggu Approval Project Leader</p>
			</div>
			<div class="icon">
				<i class="fa fa-users"></i>
			</div>
			<a href="<?= base_url('approval_spk_project_leader') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
	<div class="col-md-4">
		<div class="small-box bg-red">
			<div class="inner">
				<h3><?= $count_spk_manager_sales ?></h3>
				<p>SPK Penawaran Menunggu Approval Manager Sales</p>
			</div>
			<div class="icon">
				<i class="fa fa-user-md"></i>
			</div>
			<a href="<?= base_url('approval_spk_manager_sales') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
	<div class="col-md-4">
		<div class="small-box bg-purple">
			<div class="inner">
				<h3><?= $count_spk_direktur ?></h3>
				<p>SPK Penawaran Menunggu Approval Direktur</p>
			</div>
			<div class="icon">
				<i class="fa fa-user-secret"></i>
			</div>
			<a href="<?= base_url('approval_spk_direktur') ?>" class="small-box-footer">
				Lihat Detail <i class="fa fa-arrow-circle-right"></i>
			</a>
		</div>
	</div>
</div>

<?php if ($can_view_project_budgeting): ?>
<div class="row row-equal-cards">
	<div class="col-md-4 col-xs-12">
		<a href="<?= base_url('approval_project_budgeting') ?>" class="small-box dashboard-budgeting-card">
			<div class="dashboard-budgeting-card__core">
				<div class="inner">
					<h3><?= number_format($count_project_budgeting) ?></h3>
					<p>Project Budgeting Menunggu Approval</p>
				</div>
				<div class="dashboard-budgeting-card__icon" aria-hidden="true">
					<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
						<path d="M14 6h25l11 11v41H14zM39 6v12h11M22 25h20M22 33h12M22 41h10M22 49h8" />
						<circle cx="43" cy="44" r="13" />
						<path d="M38 44l3 3 7-7" />
					</svg>
				</div>
				<span class="small-box-footer dashboard-budgeting-card__footer">
					Lihat Detail <span class="dashboard-budgeting-card__arrow" aria-hidden="true">&rarr;</span>
				</span>
			</div>
		</a>
	</div>
</div>
<?php endif; ?>

<style>
	.small-box.dashboard-budgeting-card {
		padding: 3px;
		border-radius: 8px;
		background: #086a62;
		color: #fff;
		text-decoration: none;
		box-shadow: 0 4px 14px rgba(8, 106, 98, 0.09);
		transition: transform 220ms cubic-bezier(0.32, 0.72, 0, 1);
	}

	.dashboard-budgeting-card__core {
		position: relative;
		display: flex;
		flex: 1;
		flex-direction: column;
		overflow: hidden;
		border-radius: 5px;
		background: #087f75;
		box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.14);
	}

	.dashboard-budgeting-card .inner {
		position: relative;
		z-index: 1;
		padding: 10px 90px 10px 10px;
	}

	.dashboard-budgeting-card__icon {
		position: absolute;
		top: 10px;
		right: 12px;
		width: 76px;
		color: rgba(255, 255, 255, 0.28);
		pointer-events: none;
	}

	.dashboard-budgeting-card__icon svg {
		display: block;
		width: 100%;
		height: auto;
	}

	.dashboard-budgeting-card__footer {
		display: flex;
		padding: 3px 0;
		color: rgba(255, 255, 255, 0.9);
		background: rgba(0, 0, 0, 0.1);
		align-items: center;
		justify-content: center;
		gap: 6px;
	}

	.dashboard-budgeting-card__arrow {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 18px;
		height: 18px;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.14);
		transition: transform 220ms cubic-bezier(0.32, 0.72, 0, 1);
	}

	.small-box.dashboard-budgeting-card:hover {
		color: #fff;
		transform: translateY(-2px);
	}

	.dashboard-budgeting-card:hover .dashboard-budgeting-card__arrow {
		transform: translateX(2px);
	}

	.small-box.dashboard-budgeting-card:focus {
		outline: 3px solid #085950;
		outline-offset: 3px;
		color: #fff;
	}

	.small-box.dashboard-budgeting-card:active {
		transform: scale(0.99);
	}

	@media (prefers-reduced-motion: reduce) {
		.small-box.dashboard-budgeting-card,
		.dashboard-budgeting-card__arrow {
			transition: none;
			transform: none !important;
		}
	}

	.row-equal-cards {
		display: flex;
		flex-wrap: wrap;
	}

	.row-equal-cards>[class*="col-"] {
		display: flex;
		flex-direction: column;
	}

	.row-equal-cards .small-box {
		flex: 1;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
	}

	.row-equal-cards .small-box .inner {
		flex: 1;
	}

	.mid {
		vertical-align: middle !important;
	}

	.chosen-select {
		min-width: 200px !important;
		max-width: 100% !important;
	}

	.bold {
		font-weight: bold !important;
		padding-right: 20px !important;
	}
</style>
<script type="text/javascript">
	$(document).on('change', '#sales_order, #sales_order2', function() {
		var noso = $('#sales_order').val();
		var noso2 = $('#sales_order2').val();
		window.location.href = base_url + 'dashboard/index/' + noso + '/' + noso2;
	});

	$(document).on('click', '.addPart', function() {
		var get_id = $(this).parent().parent().attr('id');
		var split_id = get_id.split('_');
		var id = parseInt(split_id[1]) + 1;
		var id_bef = split_id[1];

		$.ajax({
			url: base_url + 'dashboard/get_add/' + id,
			cache: false,
			type: "POST",
			dataType: "json",
			success: function(data) {
				$("#exis_" + id_bef).before(data.header);
				$("#exis_" + id_bef).remove();
				$('.chosen_select').select2();
			},
			error: function() {
				swal({
					title: "Error Message !",
					text: 'Connection Time Out. Please try again..',
					type: "warning",
					timer: 3000,
					showCancelButton: false,
					showConfirmButton: false,
					allowOutsideClick: false
				});
			}
		});
	});

	$(document).on('click', '.addPart2', function() {
		var get_id = $(this).parent().parent().attr('id');
		var split_id = get_id.split('_');
		var id = parseInt(split_id[1]) + 1;
		var id_bef = split_id[1];

		$.ajax({
			url: base_url + 'dashboard/get_add2/' + id,
			cache: false,
			type: "POST",
			dataType: "json",
			success: function(data) {
				$("#doha_" + id_bef).before(data.header);
				$("#doha_" + id_bef).remove();
				$('.chosen_select').select2();
			},
			error: function() {
				swal({
					title: "Error Message !",
					text: 'Connection Time Out. Please try again..',
					type: "warning",
					timer: 3000,
					showCancelButton: false,
					showConfirmButton: false,
					allowOutsideClick: false
				});
			}
		});
	});

	$(document).on('click', '.delPart', function() {
		var get_id = $(this).parent().parent().attr('class');
		$("." + get_id).remove();
	});

	$(document).on('change', '.salesorder', function() {
		var get_id = $(this).parent().parent().attr('class');
		var split_id = get_id.split('_');
		var id_bef = split_id[1];
		// console.log(id_bef);
		var type = $(this).data('type');
		var value = $(this).val();

		var over1 = $('#exis_over_1').val();
		var over2 = $('#doha_over_1').val();

		var SUM_ORDER = 0;
		var SUM_PROPOSE = 0;
		var SUM_FG = 0;
		var SUM_BAL = 0;
		var progres = 0;

		$.ajax({
			url: base_url + 'dashboard/get_result/' + type + '/' + value,
			cache: false,
			type: "POST",
			data: {
				'over1': over1,
				'over2': over2,
				'nomor': id_bef
			},
			dataType: "json",
			success: function(data) {
				$("#" + data.id + "_order_" + id_bef).html(data.order);
				$("#" + data.id + "_propose_" + id_bef).html(data.propose);
				$("#" + data.id + "_fg_" + id_bef).html(data.fg);
				$("#" + data.id + "_balance_" + id_bef).html(data.bal);
				$("#" + data.id + "_progress_" + id_bef).html(data.progres);
				if (data.nomor == '1') {
					$("#" + data.id + "_over_1").val(data.over);
				}
				//order
				$(".order").each(function() {
					SUM_ORDER += Number($(this).html().split(",").join(""));
				});
				$(".tot_order").html(SUM_ORDER);
				//propose
				$(".propose").each(function() {
					SUM_PROPOSE += Number($(this).html().split(",").join(""));
				});
				$(".tot_propose").html(SUM_PROPOSE);
				//FG
				$(".fg").each(function() {
					SUM_FG += Number($(this).html().split(",").join(""));
				});
				$(".tot_fg").html(SUM_FG);
				//BAL
				$(".bal").each(function() {
					SUM_BAL += Number($(this).html().split(",").join(""));
				});
				$(".tot_bal").html(SUM_BAL);
				//PROCESS
				if (SUM_FG > 0 && SUM_PROPOSE > 0) {
					progres = (SUM_FG / SUM_PROPOSE) * 100;
				}
				$(".tot_progress").html(number_format(progres, 2) + ' %');

			},
			error: function() {
				swal({
					title: "Error Message !",
					text: 'Connection Time Out. Please try again..',
					type: "warning",
					timer: 3000,
					showCancelButton: false,
					showConfirmButton: false,
					allowOutsideClick: false
				});
			}
		});
	});

	function getNum(val) {
		if (isNaN(val) || val == '') {
			return 0;
		}
		return parseFloat(val);
	}

	function number_format(number, decimals, dec_point, thousands_sep) {
		// Strip all characters but numerical ones.
		number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
		var n = !isFinite(+number) ? 0 : +number,
			prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
			sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
			dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
			s = '',
			toFixedFix = function(n, prec) {
				var k = Math.pow(10, prec);
				return '' + Math.round(n * k) / k;
			};
		// Fix for IE parseFloat(0.55).toFixed(0) = 0;
		s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
		if (s[0].length > 3) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
		}
		if ((s[1] || '').length < prec) {
			s[1] = s[1] || '';
			s[1] += new Array(prec - s[1].length + 1).join('0');
		}
		return s.join(dec);
	}
</script>
