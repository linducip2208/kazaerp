<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('journals', [\App\Http\Controllers\Finance\JournalController::class,'index'])->name('journals.index')->middleware('perm:finance.view');
    Route::get('journals/create', [\App\Http\Controllers\Finance\JournalController::class,'create'])->name('journals.create')->middleware('perm:finance.post');
    Route::post('journals', [\App\Http\Controllers\Finance\JournalController::class,'store'])->name('journals.store')->middleware('perm:finance.post');
    Route::post('journals/{id}/reverse', [\App\Http\Controllers\Finance\JournalController::class,'reverse'])->name('journals.reverse')->middleware('perm:finance.approve');
    Route::resource('expenses', \App\Http\Controllers\Finance\ExpenseController::class)->middleware('perm:finance.view');
    Route::post('expenses/{id}/approve', [\App\Http\Controllers\Finance\ExpenseController::class,'approve'])->name('expenses.approve')->middleware('perm:finance.approve');
    Route::get('ledger', [\App\Http\Controllers\Finance\FinanceReportController::class,'ledger'])->name('ledger')->middleware('perm:finance.view');
    Route::get('trial', [\App\Http\Controllers\Finance\FinanceReportController::class,'trialBalance'])->name('trial')->middleware('perm:finance.view');
    Route::get('pl', [\App\Http\Controllers\Finance\FinanceReportController::class,'pl'])->name('pl')->middleware('perm:finance.view');
    Route::get('aging', [\App\Http\Controllers\Finance\FinanceReportController::class,'aging'])->name('aging')->middleware('perm:finance.view');
    Route::get('intercompany', [\App\Http\Controllers\Finance\FinanceReportController::class,'intercompany'])->name('intercompany')->middleware('perm:finance.view');
    Route::get('bankrec', [\App\Http\Controllers\Finance\BankRecController::class,'index'])->name('bankrec.index')->middleware('perm:finance.view');
    Route::get('bankrec/create', [\App\Http\Controllers\Finance\BankRecController::class,'create'])->name('bankrec.create')->middleware('perm:finance.post');
    Route::post('bankrec', [\App\Http\Controllers\Finance\BankRecController::class,'store'])->name('bankrec.store')->middleware('perm:finance.post');
    Route::post('bankrec/{id}/confirm', [\App\Http\Controllers\Finance\BankRecController::class,'confirm'])->name('bankrec.confirm')->middleware('perm:finance.approve');
    Route::get('budget', [\App\Http\Controllers\Finance\FinanceReportController::class,'budget'])->name('budget')->middleware('perm:finance.view');
    Route::get('closing', [\App\Http\Controllers\Finance\FinanceReportController::class,'closing'])->name('closing')->middleware('perm:finance.close');
    Route::post('closing/close', [\App\Http\Controllers\Finance\FinanceReportController::class,'doClose'])->name('closing.close')->middleware('perm:finance.close');
    Route::post('closing/reopen', [\App\Http\Controllers\Finance\FinanceReportController::class,'reopen'])->name('closing.reopen')->middleware('perm:finance.close');
});
Route::middleware(['auth','ctx'])->prefix('hr')->name('hr.')->group(function () {
    Route::resource('employees', \App\Http\Controllers\Hr\EmployeeController::class)->middleware('perm:hr.view');
    Route::get('attendance', [\App\Http\Controllers\Hr\AttendanceController::class,'index'])->name('attendance.index')->middleware('perm:hr.view');
    Route::post('attendance', [\App\Http\Controllers\Hr\AttendanceController::class,'store'])->name('attendance.store')->middleware('perm:hr.manage');
    Route::get('payroll', [\App\Http\Controllers\Hr\PayrollController::class,'index'])->name('payroll.index')->middleware('perm:hr.payroll');
    Route::get('payroll/create', [\App\Http\Controllers\Hr\PayrollController::class,'create'])->name('payroll.create')->middleware('perm:hr.payroll');
    Route::post('payroll', [\App\Http\Controllers\Hr\PayrollController::class,'store'])->name('payroll.store')->middleware('perm:hr.payroll');
    Route::post('payroll/{id}/approve', [\App\Http\Controllers\Hr\PayrollController::class,'approve'])->name('payroll.approve')->middleware('perm:hr.payroll');
    Route::get('payslip/{id}/pdf', [\App\Http\Controllers\Hr\PayrollController::class,'payslip'])->name('payslip.pdf')->middleware('perm:hr.view');
});
