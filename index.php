<?php
session_start();
 
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IntePros Federal Celios.AI CRM</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="192x192" href="favicon-192.png">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); position: relative; }
        .header-spacer { flex: 1; }
        .header-logo { position: absolute; left: 50%; transform: translateX(-50%); font-weight: bold; font-size: 1.3em; color: #667eea; white-space: nowrap; }
        .header-user-info { text-align: right; flex: 1; display: flex; justify-content: flex-end; align-items: center; gap: 10px; }
        .nav-tabs { display: flex; flex-wrap: wrap; gap: 5px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 5px; margin-bottom: 30px; }
        .nav-tab { flex: 1; min-width: 100px; background: transparent; border: none; padding: 12px 15px; border-radius: 8px; cursor: pointer; color: white; font-weight: 500; transition: all 0.3s ease; font-size: 1.05rem; line-height: 1.3; position: relative; display: flex; align-items: center; justify-content: center; }
        .nav-tab.active { background: rgba(255, 255, 255, 0.25); font-weight: 700; }
        .nav-tab.active::after { content: ''; position: absolute; bottom: 0; left: 25%; right: 25%; height: 3px; background: white; border-radius: 2px; }
        .nav-tab:hover { background: rgba(255, 255, 255, 0.15); }
        .tab-content { display: none; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 30px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #444; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .date-tbd-container { display: flex; align-items: center; gap: 15px; }
        .date-tbd-container input[type="date"] { flex: 1; }
        .tbd-toggle { display: flex; align-items: center; gap: 8px; cursor: pointer; white-space: nowrap; }
        .tbd-toggle input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #667eea; }
        .tbd-label { font-weight: 600; color: #667eea; font-size: 0.9rem; }
        .date-tbd-container input[type="date"]:disabled { background: #f0f0f0; color: #999; cursor: not-allowed; }
        .btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: transform 0.2s, box-shadow 0.2s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
        .btn-small { padding: 8px 16px; font-size: 0.85rem; }
        .welcome-link { cursor: pointer; padding: 8px 15px; border-radius: 8px; background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 2px solid #667eea; font-weight: 500; color: #667eea; text-decoration: none; margin-right: 15px; transition: all 0.2s; }
        .welcome-link:hover { background: linear-gradient(45deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2)); }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .data-table th { background: #f8f9fa; font-weight: 600; color: #444; }
        .data-table tr:hover { background: #f0f4ff; }
        #companyContactsTable { table-layout: fixed; }
        #companyContactsTable td { word-wrap: break-word; overflow-wrap: break-word; vertical-align: middle; }
        .action-buttons { display: flex; gap: 5px; }
        .action-btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 0.8rem; transition: opacity 0.2s; }
        .action-btn:hover { opacity: 0.8; }
        .action-btn.edit { background: #007bff; }
        .action-btn.delete { background: #dc3545; }
        .action-btn.view { background: #6c757d; }
        .action-btn.complete { background: #28a745; }
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .dashboard-card { background: white; color: #333; padding: 25px; border-radius: 15px; text-align: center; border-left: 5px solid #667eea; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
        .dashboard-card:nth-child(2) { border-left-color: #764ba2; }
        .dashboard-card:nth-child(3) { border-left-color: #28a745; }
        .dashboard-card:nth-child(4) { border-left-color: #ffc107; }
        .dashboard-card:nth-child(5) { border-left-color: #17a2b8; }
        .dashboard-card h3 { font-size: 2.5em; margin-bottom: 5px; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .dashboard-card p { color: #6c757d; }
        /* Events filter bar */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; align-items: center; }
        .filter-bar input { flex: 1; min-width: 200px; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: border-color 0.2s, box-shadow 0.2s; }
        .filter-bar input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); }
        .filter-bar select { padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; background: white; font-size: 14px; min-width: 150px; }
        .search-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; align-items: center; }
        .search-bar input { flex: 1; min-width: 200px; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; transition: border-color 0.2s, box-shadow 0.2s; }
        .search-bar input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); }
        .search-bar select { padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; background: white; min-width: 180px; }
        .modal { display: none; position: fixed; z-index: 1100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 700px; max-height: 85vh; overflow-y: auto; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #333; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .status-active, .status-met, .status-completed, .status-won, .status-done { background: #d4edda; color: #155724; }
        .status-converted { background: #e2e3e5; color: #383d41; }
        .status-bid { background: #cce5ff; color: #004085; }
        .status-lead { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .converted-row { opacity: 0.7; background: #f8f9fa; }
        .converted-badge { margin-right: 5px; cursor: help; }
        .status-inactive, .status-notmet, .status-lost { background: #f8d7da; color: #721c24; }
        .status-nobid { background: #e2e3e5; color: #383d41; }
        .status-pending, .status-open, .status-qualified, .status-draft { background: #fff3cd; color: #856404; }
        .status-inprogress, .status-submitted, .status-underreview { background: #cce5ff; color: #004085; }
        .status-backlog, .status-todo { background: #e9ecef; color: #495057; }
        .status-review { background: #e2d9f3; color: #6f42c1; }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
        [data-hidden] { display: none !important; }
        /* Loading Spinner Overlay */
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.85); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .loading-spinner { width: 48px; height: 48px; border: 4px solid #e1e5e9; border-top-color: #667eea; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-overlay p { margin-top: 16px; color: #555; font-weight: 500; font-size: 1rem; }
        /* Toast Notifications */
        .toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 10000; display: flex; flex-direction: column-reverse; gap: 10px; }
        .toast { padding: 14px 22px; border-radius: 10px; color: #fff; font-weight: 500; font-size: 0.92rem; box-shadow: 0 4px 16px rgba(0,0,0,0.18); animation: toastIn 0.3s ease; max-width: 400px; display: flex; align-items: center; gap: 10px; }
        .toast.success { background: #28a745; }
        .toast.error { background: #dc3545; }
        .toast.info { background: #667eea; }
        .toast.warning { background: #ffc107; color: #333; }
        .toast.fade-out { animation: toastOut 0.3s ease forwards; }
        @keyframes toastIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(20px); } }
        /* Empty State */
        .empty-state-msg { text-align: center; padding: 40px 20px; color: #6c757d; }
        .empty-state-msg p { font-size: 1.05rem; margin-bottom: 5px; }
        .empty-state-msg small { font-size: 0.85rem; opacity: 0.7; }
        .section-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; }
        .section-title { font-size: 1.1rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 10px; }
        .section-count { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border-radius: 20px; padding: 2px 12px; font-size: 0.85rem; }
        .link-btn { background: none; border: none; color: #667eea; cursor: pointer; text-decoration: underline; font-size: 0.85rem; }
        .agency-header { background: linear-gradient(90deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15)); padding: 10px 12px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #667eea; border-left: 4px solid #667eea; }
        .toggle { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
        .toggle-slider { position: relative; width: 44px; height: 22px; background: #e9ecef; border-radius: 22px; transition: background 0.2s ease; }
        .toggle-slider.active { background: linear-gradient(45deg, #667eea, #764ba2); }
        .toggle-knob { position: absolute; height: 18px; width: 18px; left: 2px; top: 2px; background: white; border-radius: 50%; transition: transform 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .toggle-knob.active { transform: translateX(22px); }
        .owner-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 15px; background: #e9ecef; font-size: 0.8rem; color: #495057; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .owner-badge-sm { display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; border-radius: 12px; background: #f0f4ff; font-size: 0.75rem; color: #667eea; white-space: nowrap; margin: 2px; }
        .format-chip { display: inline-block; padding: 3px 10px; border-radius: 15px; background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border: 1px solid rgba(102, 126, 234, 0.3); font-size: 0.75rem; color: #667eea; margin-right: 4px; margin-bottom: 4px; }
        .clickable-name { color: #667eea; cursor: pointer; text-decoration: none; font-weight: 600; }
        .clickable-name:hover { text-decoration: underline; }
        .empty-state { text-align: center; padding: 40px; color: #6c757d; font-style: italic; }
        .detail-row { display: flex; border-bottom: 1px solid #e9ecef; padding: 12px 0; }
        .detail-label { width: 140px; font-weight: 600; color: #555; flex-shrink: 0; }
        .detail-value { flex: 1; color: #333; }
        .detail-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #667eea; }
        .detail-avatar { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(45deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .inline-input { padding: 6px 10px; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.85rem; }
        .inline-select { padding: 6px 10px; border: 2px solid #e1e5e9; border-radius: 6px; font-size: 0.85rem; background: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px; }
        .stats-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stats-card.clickable-card { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s; border: 2px solid transparent; }
        .stats-card.clickable-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); border-color: #667eea; }
        
        /* Department Calendar */
        .dept-calendar-grid { background: #e0e0e0; border-radius: 8px; overflow: hidden; width: 100%; }
        .dept-calendar-header { display: grid; grid-template-columns: repeat(7, calc(100% / 7)); }
        .dept-calendar-header-cell { background: #f8f9fa; padding: 10px; text-align: center; font-weight: 600; color: #444; border-right: 1px solid #e0e0e0; box-sizing: border-box; }
        .dept-calendar-header-cell:last-child { border-right: none; }
        .dept-calendar-days { display: grid; grid-template-columns: repeat(7, calc(100% / 7)); }
        .dept-calendar-day { background: white; min-height: 100px; padding: 5px; cursor: pointer; transition: background 0.2s; overflow: hidden; border-right: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; box-sizing: border-box; }
        .dept-calendar-day:nth-child(7n) { border-right: none; }
        .dept-calendar-day:hover { background: #f8f9fa; }
        .dept-calendar-day.other-month { background: #f5f5f5; color: #aaa; }
        .dept-calendar-day.today .dept-day-number { background: #667eea; color: white; }
        .dept-day-number { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; font-weight: 500; margin-bottom: 5px; }
        .dept-event { padding: 3px 6px; border-radius: 4px; margin-bottom: 2px; font-size: 0.75rem; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; transition: transform 0.1s; display: block; width: 100%; box-sizing: border-box; }
        .dept-event:hover { transform: scale(1.02); }
        .dept-view-btn { padding: 8px 15px !important; }
        .dept-view-btn.active { background: linear-gradient(45deg, #667eea, #764ba2) !important; color: white !important; }
        .btn-small { padding: 8px 15px; font-size: 0.85rem; }
        
        /* Department Calendar Week/Day Views */
        .dept-week-view { display: grid; grid-template-columns: 80px repeat(7, 1fr); gap: 1px; background: #e0e0e0; border-radius: 8px; overflow: hidden; }
        .dept-week-header { background: #f8f9fa; padding: 10px; text-align: center; font-weight: 600; font-size: 0.85rem; }
        .dept-week-time { background: #f8f9fa; padding: 5px; font-size: 0.75rem; text-align: right; color: #6c757d; }
        .dept-week-cell { background: white; min-height: 50px; padding: 2px; border-left: 1px solid #eee; position: relative; }
        .dept-day-view { background: white; border-radius: 8px; overflow: hidden; }
        .dept-day-header { background: #f8f9fa; padding: 15px; text-align: center; font-weight: 600; border-bottom: 1px solid #e0e0e0; }
        .dept-day-timeline { display: grid; grid-template-columns: 80px 1fr; }
        .dept-day-time { background: #f8f9fa; padding: 10px; font-size: 0.85rem; text-align: right; color: #6c757d; border-bottom: 1px solid #eee; }
        .dept-day-slot { background: white; min-height: 60px; padding: 5px; border-bottom: 1px solid #eee; border-left: 1px solid #e0e0e0; }
        
        /* Color Palette */
        .color-palette { display: flex; flex-wrap: wrap; gap: 8px; }
        .color-swatch { width: 36px; height: 36px; border-radius: 8px; cursor: pointer; border: 3px solid transparent; transition: transform 0.2s; }
        .color-swatch:hover { transform: scale(1.1); }
        .color-swatch.selected { border-color: #333; box-shadow: 0 0 0 2px white, 0 0 0 4px currentColor; }
        
        /* Recurring Event Options */
        .recurrence-options { margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
        .recurrence-end-option { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .recurrence-end-option input[type="date"], .recurrence-end-option input[type="number"] { padding: 8px 12px; border: 2px solid #e1e5e9; border-radius: 6px; }
        .recurrence-end-option input:disabled { opacity: 0.5; }
        
        /* Hierarchical Agencies View */
        .agencies-hierarchy { display: flex; flex-direction: column; gap: 0; }
        .agency-card { background: white; border: 1px solid #e0e0e0; border-radius: 0; overflow: hidden; }
        .agency-card:first-child { border-radius: 12px 12px 0 0; }
        .agency-card:last-child { border-radius: 0 0 12px 12px; }
        .agency-card:only-child { border-radius: 12px; }
        .agency-card + .agency-card { border-top: none; }
        .agency-card-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08)); border-bottom: 1px solid #e9ecef; }
        .agency-card-title { display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 1.05rem; color: #333; }
        .agency-card-meta { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #6c757d; }
        .agency-card-meta span { display: flex; align-items: center; gap: 5px; }
        .agency-card-actions { display: flex; gap: 8px; }
        .division-list { padding: 0; }
        .division-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px 12px 50px; border-bottom: 1px solid #f0f0f0; background: #fafafa; transition: background 0.2s; }
        .division-row:last-child { border-bottom: none; }
        .division-row:hover { background: #f0f5ff; }
        .division-name { display: flex; align-items: center; gap: 10px; color: #555; font-size: 0.95rem; }
        .division-name .division-arrow { color: #667eea; font-weight: bold; }
        .division-source-badge { font-size: 0.75rem; color: #17a2b8; font-style: italic; margin-left: 8px; }
        .division-actions { display: flex; gap: 5px; }
        .add-division-row { display: flex; align-items: center; gap: 10px; padding: 10px 20px 10px 50px; background: #f8f9fa; border-top: 1px dashed #ddd; }
        .add-division-row input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; }
        .add-division-row button { padding: 8px 15px; font-size: 0.85rem; }
        .btn-add-division { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 0.8rem; }
        .btn-add-division:hover { background: #218838; }
        .btn-delete-division { background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; }
        .btn-delete-division:hover { background: #c82333; }
        
        /* Contact Card Styles */
        .contacts-hierarchy-view { display: flex; flex-direction: column; }
        .contact-agency-card { background: white; border: 1px solid #e0e0e0; border-radius: 0; overflow: hidden; }
        .contact-agency-card:first-child { border-radius: 12px 12px 0 0; }
        .contact-agency-card:last-child { border-radius: 0 0 12px 12px; }
        .contact-agency-card:only-child { border-radius: 12px; }
        .contact-agency-card + .contact-agency-card { border-top: none; }
        .contact-agency-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08)); border-bottom: 1px solid #e9ecef; }
        .contact-agency-title { font-weight: 600; font-size: 1.05rem; color: #333; }
        .contact-agency-meta { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: #6c757d; }
        .contact-list { padding: 0; }
        .contact-row { display: grid; grid-template-columns: minmax(180px, 2fr) minmax(80px, 0.8fr) minmax(120px, 1.2fr) minmax(160px, 1.5fr) minmax(100px, 1fr) 85px 55px auto; gap: 12px; align-items: center; padding: 10px 20px; border-bottom: 1px solid #f0f0f0; background: #fafafa; transition: background 0.2s; }
        .contact-row:hover { background: #f0f5ff; }
        .contact-row.met { background: rgba(40, 167, 69, 0.05); }
        .contact-row.met:hover { background: rgba(40, 167, 69, 0.1); }
        .contact-row.not-met { background: rgba(220, 53, 69, 0.03); }
        .contact-row.not-met:hover { background: rgba(220, 53, 69, 0.06); }
        .contact-name-cell { display: flex; flex-direction: column; }
        .contact-name-cell .contact-name { font-weight: 600; color: #333; cursor: pointer; }
        .contact-name-cell .contact-name:hover { color: #667eea; text-decoration: underline; }
        .contact-name-cell .contact-email { font-size: 0.8rem; color: #6c757d; }
        .contact-division { color: #555; font-size: 0.9rem; }
        .contact-role { color: #555; font-size: 0.9rem; }
        .contact-owner { font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .contact-last-date { font-size: 0.85rem; color: #6c757d; text-align: center; }
        .contact-status { text-align: center; }
        .contact-toggle { display: flex; justify-content: center; }
        .contact-actions { display: flex; gap: 5px; justify-content: flex-end; }
        .contact-header-row { background: #f8f9fa !important; font-weight: 600; font-size: 0.85rem; color: #444; border-bottom: 2px solid #e0e0e0 !important; }
        .contact-header-row:hover { background: #f8f9fa !important; }
        .contact-header-row .contact-name-cell { flex-direction: row; }
        .contact-header-row .contact-last-date { text-align: center; }
        .contact-header-row .contact-status { text-align: center; }
        .contact-header-row .contact-toggle { text-align: center; }
        
        /* Search-Select Dropdown Styles */
        .search-select-container { position: relative; }
        .search-select-container input { width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; }
        .search-select-container input:focus { outline: none; border-color: #667eea; }
        .search-select-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 8px 8px; z-index: 100; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .search-select-dropdown.show { display: block; }
        .search-select-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: background 0.15s; }
        .search-select-item:hover { background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); }
        .search-select-item:last-child { border-bottom: none; }
        .search-select-item .item-title { font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px; }
        .search-select-item .item-subtitle { font-size: 0.8rem; color: #6c757d; margin-top: 2px; }
        .contact-type-badge { font-size: 0.65rem; font-weight: 600; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .contact-type-badge.federal { background: #e3f2fd; color: #1565c0; }
        .contact-type-badge.commercial { background: #f3e5f5; color: #7b1fa2; }
        .search-select-no-results { padding: 15px; text-align: center; color: #6c757d; font-style: italic; }
        .selected-item-display { margin-top: 8px; padding: 10px 15px; background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .selected-item-display .item-info { flex: 1; }
        .selected-item-display .item-name { font-weight: 600; color: #333; }
        .selected-item-display .item-detail { font-size: 0.8rem; color: #6c757d; }
        .selected-item-display .remove-btn { background: none; border: none; color: #dc3545; cursor: pointer; font-size: 1.2rem; padding: 0 5px; }
        .selected-item-display .remove-btn:hover { color: #a71d2a; }
        
        /* Calendar Styles */
        .calendar-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .calendar-title h2 { margin: 0 0 5px 0; color: #333; }
        .calendar-controls { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .calendar-select { padding: 10px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; background: white; min-width: 160px; }
        .view-toggle { display: flex; background: #f0f0f0; border-radius: 8px; padding: 4px; }
        .view-btn { padding: 8px 16px; border: none; background: transparent; cursor: pointer; border-radius: 6px; font-weight: 500; color: #666; transition: all 0.2s; }
        .view-btn.active { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .view-btn:hover:not(.active) { background: #e0e0e0; }
        .calendar-navigation { display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; }
        .nav-btn { padding: 10px 20px; border: 2px solid #e1e5e9; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .nav-btn:hover { border-color: #667eea; color: #667eea; }
        .today-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; }
        .today-btn:hover { opacity: 0.9; color: white; }
        .current-period { font-size: 1.4rem; font-weight: 600; color: #333; min-width: 200px; text-align: center; }
        .calendar-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); width: 100%; }
        
        /* Month View */
        .month-grid { display: grid; grid-template-columns: repeat(7, calc(100% / 7)); }
        .month-header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 12px; text-align: center; font-weight: 600; font-size: 0.9rem; border-right: 1px solid rgba(255,255,255,0.2); box-sizing: border-box; }
        .month-header:last-child { border-right: none; }
        .month-day { min-height: 100px; border: 1px solid #e9ecef; padding: 8px; cursor: pointer; transition: background 0.2s; vertical-align: top; overflow: hidden; box-sizing: border-box; }
        .month-day:hover { background: #f8f9fa; }
        .month-day.other-month { background: #fafafa; color: #aaa; }
        .month-day.today { background: rgba(102, 126, 234, 0.1); border-color: #667eea; }
        .month-day.selected { background: rgba(102, 126, 234, 0.15); border: 2px solid #667eea; }
        .day-number { font-weight: 600; font-size: 0.95rem; margin-bottom: 5px; color: #333; }
        .month-day.other-month .day-number { color: #bbb; }
        .month-day.today .day-number { color: #667eea; }
        .day-tasks { display: flex; flex-direction: column; gap: 3px; overflow: hidden; }
        .day-task { font-size: 0.75rem; padding: 3px 6px; border-radius: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; transition: transform 0.1s; display: block; width: 100%; box-sizing: border-box; }
        .day-task:hover { transform: scale(1.02); }
        .day-task.priority-high { background: rgba(220, 53, 69, 0.15); color: #dc3545; border-left: 3px solid #dc3545; }
        .day-task.priority-medium { background: rgba(255, 193, 7, 0.15); color: #856404; border-left: 3px solid #ffc107; }
        .day-task.priority-low { background: rgba(40, 167, 69, 0.15); color: #28a745; border-left: 3px solid #28a745; }
        .more-tasks { font-size: 0.7rem; color: #667eea; font-weight: 500; padding: 2px 6px; cursor: pointer; }
        .more-tasks:hover { text-decoration: underline; }
        
        /* Week View */
        .week-grid { display: grid; grid-template-columns: repeat(7, calc(100% / 7)); }
        .week-day-header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px 10px; text-align: center; border-right: 1px solid rgba(255,255,255,0.2); box-sizing: border-box; }
        .week-day-header:last-child { border-right: none; }
        .week-day-header .day-name { font-weight: 600; font-size: 0.85rem; }
        .week-day-header .day-date { font-size: 1.2rem; margin-top: 5px; }
        .week-day-content { min-height: 400px; border: 1px solid #e9ecef; padding: 10px; background: white; overflow: hidden; box-sizing: border-box; }
        .week-day-content.today { background: rgba(102, 126, 234, 0.05); }
        .week-task { padding: 10px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; transition: transform 0.1s, box-shadow 0.1s; overflow: hidden; }
        .week-task:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .week-task.priority-high { background: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; }
        .week-task.priority-medium { background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; }
        .week-task.priority-low { background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; }
        .week-task-title { font-weight: 600; font-size: 0.85rem; margin-bottom: 4px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .week-task-meta { font-size: 0.75rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        /* Day View */
        .day-view { padding: 20px; }
        .day-view-header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef; }
        .day-view-header h3 { margin: 0; font-size: 1.5rem; color: #333; }
        .day-view-header p { margin: 5px 0 0; color: #6c757d; }
        .day-task-list { display: flex; flex-direction: column; gap: 12px; }
        .day-task-item { display: flex; gap: 15px; padding: 15px; border-radius: 10px; cursor: pointer; transition: transform 0.1s, box-shadow 0.1s; }
        .day-task-item:hover { transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .day-task-item.priority-high { background: rgba(220, 53, 69, 0.08); border-left: 5px solid #dc3545; }
        .day-task-item.priority-medium { background: rgba(255, 193, 7, 0.08); border-left: 5px solid #ffc107; }
        .day-task-item.priority-low { background: rgba(40, 167, 69, 0.08); border-left: 5px solid #28a745; }
        .day-task-time { font-size: 0.85rem; color: #667eea; font-weight: 600; min-width: 80px; }
        .day-task-details { flex: 1; }
        .day-task-details h4 { margin: 0 0 5px; font-size: 1rem; color: #333; }
        .day-task-details p { margin: 0; font-size: 0.85rem; color: #666; }
        .day-task-badges { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .task-badge { padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 500; }
        .task-badge.status { background: #e9ecef; color: #495057; }
        .task-badge.category { background: rgba(102, 126, 234, 0.1); color: #667eea; }
        .no-tasks-message { text-align: center; padding: 60px 20px; color: #6c757d; }
        .no-tasks-message h4 { margin: 0 0 10px; color: #aaa; }
        
        /* Calendar Event Types */
        .day-task.event-task { background: rgba(102, 126, 234, 0.15); color: #667eea; border-left: 3px solid #667eea; }
        .day-task.event-proposal-submit { background: rgba(40, 167, 69, 0.15); color: #28a745; border-left: 3px solid #28a745; }
        .day-task.event-proposal-due { background: rgba(220, 53, 69, 0.15); color: #dc3545; border-left: 3px solid #dc3545; }
        .day-task.event-opportunity { background: rgba(255, 193, 7, 0.15); color: #856404; border-left: 3px solid #ffc107; }
        .week-task.event-task { background: rgba(102, 126, 234, 0.1); border-left: 4px solid #667eea; }
        .week-task.event-proposal-submit { background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; }
        .week-task.event-proposal-due { background: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; }
        .week-task.event-opportunity { background: rgba(255, 193, 7, 0.1); border-left: 4px solid #ffc107; }
        .day-task-item.event-task { background: rgba(102, 126, 234, 0.08); border-left: 5px solid #667eea; }
        .day-task-item.event-proposal-submit { background: rgba(40, 167, 69, 0.08); border-left: 5px solid #28a745; }
        .day-task-item.event-proposal-due { background: rgba(220, 53, 69, 0.08); border-left: 5px solid #dc3545; }
        .day-task-item.event-opportunity { background: rgba(255, 193, 7, 0.08); border-left: 5px solid #ffc107; }
        .event-type-badge { display: inline-block; font-size: 0.65rem; padding: 1px 5px; border-radius: 3px; margin-left: 5px; font-weight: 600; text-transform: uppercase; }
        .event-type-badge.task { background: #667eea; color: white; }
        .event-type-badge.proposal-submit { background: #28a745; color: white; }
        .event-type-badge.proposal-due { background: #dc3545; color: white; }
        .event-type-badge.opportunity { background: #ffc107; color: #333; }
        .event-type-badge.crm-event { background: #17a2b8; color: white; }
        
        /* Task Detail Panel */
        .task-detail-panel { position: fixed; top: 0; right: -450px; width: 450px; height: 100%; background: white; box-shadow: -5px 0 25px rgba(0,0,0,0.15); z-index: 1001; transition: right 0.3s ease; overflow-y: auto; }
        .task-detail-panel.open { right: 0; }
        .task-panel-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 1000; display: none; }
        .task-panel-overlay.open { display: block; }
        .task-panel-header { padding: 20px; background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .task-panel-header h3 { margin: 0 0 5px; }
        .task-panel-close { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; }
        .task-panel-body { padding: 20px; }
        .task-panel-row { padding: 12px 0; border-bottom: 1px solid #e9ecef; }
        .task-panel-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .task-panel-value { font-size: 1rem; color: #333; }
        .task-panel-actions { padding: 20px; border-top: 1px solid #e9ecef; display: flex; gap: 10px; }
        
        /* Contact Notes Panel Styles */
        .contact-panel { position: fixed; top: 0; right: -500px; width: 500px; height: 100%; background: white; box-shadow: -5px 0 25px rgba(0,0,0,0.15); z-index: 1001; overflow-y: auto; transition: right 0.3s ease; }
        .contact-panel.open { right: 0; }
        .contact-panel-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 1000; display: none; }
        .contact-panel-overlay.open { display: block; }
        .contact-panel-header { padding: 20px; background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .contact-panel-header h3 { margin: 0 0 5px; font-size: 1.3rem; }
        .contact-panel-header p { margin: 0; opacity: 0.9; }
        .contact-panel-close { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; }
        .contact-panel-close:hover { background: rgba(255,255,255,0.3); }
        .contact-panel-tabs { display: flex; border-bottom: 2px solid #e9ecef; }
        .contact-panel-tab { flex: 1; padding: 12px; text-align: center; cursor: pointer; font-weight: 500; color: #666; background: #f8f9fa; border: none; transition: all 0.2s; }
        .contact-panel-tab.active { background: white; color: #667eea; border-bottom: 2px solid #667eea; margin-bottom: -2px; }
        .contact-panel-tab:hover:not(.active) { background: #e9ecef; }
        .contact-panel-section { padding: 20px; }
        .contact-info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .contact-info-label { width: 140px; min-width: 140px; font-weight: 600; color: #555; font-size: 0.9rem; }
        .contact-info-value { flex: 1; color: #333; word-wrap: break-word; }
        .notes-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .notes-filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .notes-filter-group { display: flex; flex-direction: column; gap: 4px; }
        .notes-filter-group label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; }
        .notes-filter-group input, .notes-filter-group select { padding: 6px 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.85rem; }
        .note-card { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 12px; border-left: 4px solid #667eea; }
        .note-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .note-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .note-user { font-weight: 600; color: #333; }
        .note-date { font-size: 0.85rem; color: #6c757d; }
        .note-date-badge { font-size: 0.75rem; padding: 2px 8px; background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); border-radius: 10px; color: #667eea; }
        .note-type { font-size: 0.75rem; padding: 2px 8px; background: #e9ecef; border-radius: 10px; color: #495057; }
        .note-actions { display: flex; gap: 5px; }
        .note-action-btn { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; transition: background 0.2s; }
        .note-action-btn:hover { background: #e9ecef; }
        .note-action-btn.edit { color: #007bff; }
        .note-action-btn.delete { color: #dc3545; }
        .note-text { color: #333; line-height: 1.5; white-space: pre-wrap; }
        .no-notes { text-align: center; padding: 40px 20px; color: #6c757d; }
        .no-notes-icon { font-size: 3rem; margin-bottom: 10px; opacity: 0.5; }
        
        /* Linked Items Styles */
        .linked-section { margin-bottom: 15px; }
        .linked-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .linked-list { display: flex; flex-direction: column; gap: 8px; }
        .linked-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #667eea; }
        .linked-item.opportunity { border-left-color: #28a745; }
        .linked-item.proposal { border-left-color: #007bff; }
        .linked-item-info { flex: 1; }
        .linked-item-title { font-weight: 600; color: #333; font-size: 0.9rem; }
        .linked-item-meta { font-size: 0.8rem; color: #6c757d; display: flex; gap: 10px; margin-top: 3px; }
        .linked-item-status { padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; background: #e9ecef; }
        .linked-item-status.open, .linked-item-status.lead, .linked-item-status.draft { background: #d4edda; color: #155724; }
        .linked-item-status.won { background: #cce5ff; color: #004085; }
        .linked-item-status.lost, .linked-item-status.cancelled { background: #f8d7da; color: #721c24; }
        .linked-item-actions { display: flex; gap: 5px; }
        .linked-unlink-btn { background: none; border: none; cursor: pointer; color: #dc3545; font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; }
        .linked-unlink-btn:hover { background: #f8d7da; }
        .no-linked { text-align: center; padding: 20px; color: #6c757d; font-size: 0.9rem; }
        
        /* Company Panel Styles */
        .company-panel-section-title { font-weight: 600; color: #667eea; font-size: 0.95rem; padding: 10px 0 8px 0; border-bottom: 2px solid #667eea; margin-bottom: 5px; }
        .company-contacts-list { display: flex; flex-direction: column; gap: 10px; }
        .company-contact-card { background: #f8f9fa; border-radius: 10px; padding: 15px; cursor: pointer; transition: all 0.2s ease; border-left: 4px solid #667eea; }
        .company-contact-card:hover { background: #e9ecef; transform: translateX(5px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .company-contact-card-name { font-weight: 600; color: #333; font-size: 1rem; margin-bottom: 4px; }
        .company-contact-card-title { color: #666; font-size: 0.9rem; margin-bottom: 8px; }
        .company-contact-card-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
        .company-contact-card-email { color: #667eea; font-size: 0.85rem; }
        
        /* Opportunity Panel Styles (reuses contact panel styles) */
        .opp-panel { position: fixed; top: 0; right: -550px; width: 550px; height: 100%; background: white; box-shadow: -5px 0 25px rgba(0,0,0,0.15); z-index: 1001; overflow-y: auto; transition: right 0.3s ease; }
        .opp-panel.open { right: 0; }
        .opp-panel-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 1000; display: none; }
        .opp-panel-overlay.open { display: block; }
        
        /* Multi-Select Styles */
        .multi-select-container { border: 2px solid #e1e5e9; border-radius: 8px; padding: 10px; min-height: 50px; }
        .multi-select-search { width: 100%; border: none; padding: 8px; font-size: 14px; outline: none; }
        .multi-select-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .multi-select-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: linear-gradient(45deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15)); border-radius: 15px; font-size: 0.85rem; color: #333; }
        .multi-select-tag .remove-tag { cursor: pointer; color: #dc3545; font-weight: bold; margin-left: 3px; }
        .multi-select-tag .remove-tag:hover { color: #a71d2a; }
        .multi-select-dropdown { position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 8px 8px; z-index: 100; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .multi-select-dropdown.show { display: block; }
        .multi-select-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .multi-select-item:hover { background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1)); }
        .multi-select-item.selected { background: #e9ecef; }
        .multi-select-wrapper { position: relative; }
        
        /* Assignment Section Styles */
        .assignment-section { margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px; }
        .assignment-section h5 { margin: 0 0 10px 0; color: #667eea; font-size: 0.9rem; }
        .assigned-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .assigned-chip { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: white; border: 1px solid #e1e5e9; border-radius: 20px; font-size: 0.85rem; }
        .assigned-chip .chip-remove { cursor: pointer; color: #dc3545; margin-left: 3px; }
        .assigned-chip .chip-remove:hover { color: #a71d2a; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .header-spacer { display: none; }
            .header-logo { position: static; transform: none; }
            .header-user-info { text-align: center; justify-content: center; flex-wrap: wrap; }
            .nav-tab { min-width: 80px; padding: 10px; font-size: 0.9rem; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .calendar-header { flex-direction: column; }
            .calendar-controls { width: 100%; justify-content: center; }
            .month-day { min-height: 70px; padding: 4px; }
            .day-task { font-size: 0.65rem; padding: 2px 4px; }
            .task-detail-panel { width: 100%; right: -100%; }
            .kanban-board { flex-direction: column; }
            .kanban-column { min-width: 100%; }
            .contacts-hierarchy-view { overflow-x: auto; }
            .contact-row { min-width: 900px; font-size: 0.85rem; }
            .contact-agency-meta { flex-direction: column; gap: 5px; }
        }
        
        /* Spicy Kanban Styles */
        .kanban-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .kanban-title { display: flex; align-items: center; gap: 10px; }
        .kanban-title h2 { margin: 0; color: #333; }
        .kanban-filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .kanban-select { padding: 10px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; background: white; min-width: 160px; }
        .kanban-select:focus { outline: none; border-color: #667eea; }
        
        /* Kanban Date Navigation Styles */
        .kanban-date-nav { display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 5px 10px; border-radius: 8px; border: 2px solid #e1e5e9; }
        .kanban-nav-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; font-size: 0.85rem; }
        .kanban-nav-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4); }
        .kanban-today-btn { background: #28a745; margin-left: 5px; }
        .kanban-today-btn:hover { box-shadow: 0 2px 8px rgba(40, 167, 69, 0.4); }
        .kanban-date-label { font-weight: 600; color: #333; min-width: 140px; text-align: center; font-size: 0.9rem; }
        
        .kanban-board { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 20px; min-height: 500px; }
        .kanban-column { flex: 1; min-width: 280px; max-width: 320px; background: #f4f5f7; border-radius: 12px; display: flex; flex-direction: column; }
        .kanban-column-header { padding: 15px; font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .kanban-column-header .column-count { background: rgba(255,255,255,0.3); padding: 2px 10px; border-radius: 12px; font-size: 0.8rem; }
        
        .kanban-column.backlog .kanban-column-header { background: linear-gradient(135deg, #6c757d, #495057); color: white; }
        .kanban-column.pending .kanban-column-header { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; }
        .kanban-column.inprogress .kanban-column-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .kanban-column.review .kanban-column-header { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; }
        .kanban-column.done .kanban-column-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        
        .kanban-column-body { flex: 1; padding: 10px; overflow-y: auto; min-height: 400px; }
        .kanban-column-body.drag-over { background: rgba(102, 126, 234, 0.1); border: 2px dashed #667eea; border-radius: 0 0 12px 12px; }
        
        .kanban-card { background: white; border-radius: 8px; padding: 12px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: grab; transition: transform 0.2s, box-shadow 0.2s; border-left: 4px solid #e9ecef; }
        .kanban-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .kanban-card.dragging { opacity: 0.5; transform: rotate(3deg); }
        .kanban-card.priority-high { border-left-color: #dc3545; }
        .kanban-card.priority-medium { border-left-color: #ffc107; }
        .kanban-card.priority-low { border-left-color: #28a745; }
        
        .kanban-card-title { font-weight: 600; color: #333; margin-bottom: 8px; font-size: 0.95rem; }
        .kanban-card-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .kanban-card-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; background: #e9ecef; color: #495057; }
        .kanban-card-badge.due-date { background: #e3f2fd; color: #1565c0; }
        .kanban-card-badge.priority-high { background: rgba(220,53,69,0.1); color: #dc3545; }
        .kanban-card-badge.priority-medium { background: rgba(255,193,7,0.1); color: #856404; }
        .kanban-card-badge.priority-low { background: rgba(40,167,69,0.1); color: #28a745; }
        .kanban-card-badge.assigned { background: #f3e5f5; color: #7b1fa2; }
        
        .kanban-card-related { font-size: 0.8rem; color: #667eea; margin-bottom: 6px; font-weight: 500; }
        .kanban-card-description { font-size: 0.8rem; color: #6c757d; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .kanban-empty { text-align: center; padding: 30px 15px; color: #aaa; font-style: italic; font-size: 0.85rem; }
        
        .kanban-add-btn { width: 100%; padding: 10px; background: transparent; border: 2px dashed #ccc; border-radius: 8px; color: #999; cursor: pointer; transition: all 0.2s; margin-top: 10px; }
        .kanban-add-btn:hover { border-color: #667eea; color: #667eea; background: rgba(102, 126, 234, 0.05); }
        
        /* Sub-tabs for Contacts section */
        .sub-tabs { display: flex; gap: 5px; margin-bottom: 20px; background: rgba(102, 126, 234, 0.1); border-radius: 10px; padding: 5px; }
        .sub-tab { flex: 1; background: transparent; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 500; color: #667eea; transition: all 0.3s ease; font-size: 0.95rem; }
        .sub-tab:hover { background: rgba(255, 255, 255, 0.5); }
        .sub-tab.active { background: white; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-bottom: 3px solid #667eea; font-weight: 600; }
        .sub-tab-content { display: none; }
        .sub-tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        
        /* Company badges */
        .company-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 500; margin: 1px 2px; }
        .company-badge.sbs { background: #e3f2fd; color: #1565c0; }
        .company-badge.vehicle { background: #f3e5f5; color: #7b1fa2; }
        .company-badge.posture-partner { background: #e8f5e9; color: #2e7d32; }
        .company-badge.posture-competitor { background: #ffebee; color: #c62828; }
        .company-badge.posture-both { background: #fff3e0; color: #e65100; }
        .company-badge.strategic-anchor { background: #e8f5e9; color: #2e7d32; font-weight: 600; }
        .company-badge.strategic-strategic { background: #e3f2fd; color: #1565c0; }
        .company-badge.strategic-opportunistic { background: #f5f5f5; color: #616161; }
        
        /* Multi-select checkboxes */
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e5e9; }
        .checkbox-item { display: flex; align-items: center; gap: 6px; padding: 6px 12px; background: white; border-radius: 6px; cursor: pointer; transition: all 0.2s; border: 1px solid #e1e5e9; }
        .checkbox-item:hover { border-color: #667eea; }
        .checkbox-item input[type="checkbox"] { width: auto !important; flex-shrink: 0; margin: 0; cursor: pointer; }
        .checkbox-item span { white-space: nowrap; font-size: 0.9rem; }
        .checkbox-item.checked { background: #667eea; border-color: #667eea; }
        .checkbox-item.checked span { color: white; }
        
        /* Agency multi-select for company contacts */
        .agency-select-container { max-height: 200px; overflow-y: auto; border: 1px solid #e1e5e9; border-radius: 8px; padding: 10px; background: #f8f9fa; }
        .agency-select-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: white; border-radius: 6px; margin-bottom: 5px; cursor: pointer; border: 1px solid transparent; transition: all 0.2s; }
        .agency-select-item:hover { border-color: #667eea; }
        .agency-select-item.selected { background: #667eea; color: white; }
        .agency-select-item input[type="checkbox"] { width: auto; flex-shrink: 0; }
        .agency-select-search { width: 100%; padding: 8px 12px; border: 1px solid #e1e5e9; border-radius: 6px; margin-bottom: 10px; }
        
        /* Company contact detail modal */
        .company-contact-agencies-list { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px; }
        .company-contact-agency-tag { background: #e3f2fd; color: #1565c0; padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; }
        
        /* Parent company prompt */
        .parent-company-prompt { background: #fff3e0; border: 1px solid #ffcc80; border-radius: 8px; padding: 15px; margin-top: 10px; }
        .parent-company-prompt h4 { margin: 0 0 10px 0; color: #e65100; }
        .parent-company-prompt-buttons { display: flex; gap: 10px; margin-top: 10px; }
        
        /* ============================================================================
           JIRA INTEGRATION STYLES
           ============================================================================ */
        .jira-connect-container { display: flex; justify-content: center; align-items: center; min-height: 400px; padding: 40px; }
        .jira-connect-card { background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); padding: 56px; text-align: center; max-width: 420px; }
        .jira-logo { margin-bottom: 28px; }
        .jira-connect-card h2 { margin: 0 0 12px 0; color: #172B4D; font-size: 26px; font-weight: 600; }
        .jira-connect-card p { margin: 0 0 28px 0; color: #5E6C84; line-height: 1.6; font-size: 15px; }
        .jira-connect-btn { background: linear-gradient(135deg, #0052CC 0%, #2684FF 100%); color: #fff; border: none; padding: 16px 32px; font-size: 16px; font-weight: 600; border-radius: 8px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .jira-connect-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 82, 204, 0.4); }
        .jira-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: #fff; padding: 16px 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); }
        .jira-header-left { display: flex; align-items: center; gap: 12px; }
        .jira-header-left h2 { margin: 0; color: #172B4D; font-size: 22px; font-weight: 600; }
        .jira-project-badge { background: linear-gradient(135deg, #0052CC 0%, #2684FF 100%); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; }
        .jira-header-right { display: flex; align-items: center; gap: 12px; }
        .jira-view-toggle { display: flex; background: #F4F5F7; border-radius: 8px; padding: 4px; }
        .jira-view-toggle .view-btn { background: transparent; border: none; padding: 10px 14px; cursor: pointer; border-radius: 6px; color: #5E6C84; display: flex; align-items: center; transition: all 0.2s; }
        .jira-view-toggle .view-btn.active { background: #fff; color: #0052CC; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); }
        .jira-user-badge { display: flex; align-items: center; gap: 10px; background: #F4F5F7; padding: 8px 16px; border-radius: 24px; }
        .jira-user-avatar { width: 28px; height: 28px; border-radius: 50%; }
        .jira-user-badge span { font-size: 14px; color: #172B4D; font-weight: 500; }
        .jira-board-container { position: relative; min-height: 400px; }
        .jira-kanban { display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; }
        .jira-kanban-column { flex: 0 0 300px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04); overflow: hidden; }
        .jira-kanban-header { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 4px solid; background: #FAFBFC; }
        .jira-kanban-header .status-name { font-weight: 600; color: #172B4D; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .jira-kanban-header .status-count { background: #DFE1E6; color: #5E6C84; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .jira-kanban-cards { padding: 12px; display: flex; flex-direction: column; gap: 10px; min-height: 400px; }
        .jira-card { background: #fff; border-radius: 10px; padding: 14px; border: 1px solid #E8EAED; cursor: pointer; transition: all 0.2s; }
        .jira-card:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transform: translateY(-2px); }
        .jira-card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .jira-issue-type { font-size: 14px; }
        .jira-issue-key { font-size: 12px; color: #0052CC; font-weight: 600; }
        .jira-card-summary { font-size: 14px; color: #172B4D; line-height: 1.5; margin-bottom: 14px; }
        .jira-card-footer { display: flex; justify-content: space-between; align-items: center; }
        .jira-assignee img { width: 24px; height: 24px; border-radius: 50%; }
        .jira-avatar-placeholder { width: 24px; height: 24px; border-radius: 50%; background: #DFE1E6; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #5E6C84; font-weight: 600; }
        .crm-link-indicator { font-size: 14px; }
        .jira-list-table { width: 100%; border-collapse: collapse; }
        .jira-list-table th, .jira-list-table td { padding: 16px; text-align: left; border-bottom: 1px solid #E8EAED; }
        .jira-list-table th { background: #FAFBFC; font-weight: 600; color: #5E6C84; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .jira-list-table tr:hover { background: #FAFBFC; }
        .jira-status-badge { display: inline-block; padding: 5px 12px; border-radius: 6px; color: #fff; font-size: 12px; font-weight: 600; }
        .crm-link-badge { background: #E3FCEF; color: #006644; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; }
        .jira-modal .modal-content { max-width: 700px; max-height: 90vh; overflow-y: auto; }
        .jira-modal-content { padding: 24px; }
        .jira-issue-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .jira-issue-type-badge { background: #DEEBFF; color: #0052CC; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
        .jira-issue-key-link { color: #5E6C84; text-decoration: none; font-size: 14px; }
        .jira-issue-key-link:hover { color: #0052CC; text-decoration: underline; }
        .jira-issue-title { margin: 0 0 24px 0; color: #172B4D; font-size: 22px; line-height: 1.4; }
        .jira-issue-meta { display: flex; gap: 32px; padding: 20px; background: #F8F9FA; border-radius: 12px; margin-bottom: 28px; }
        .jira-meta-item { display: flex; flex-direction: column; gap: 8px; }
        .jira-meta-item label { font-size: 11px; color: #5E6C84; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
        .jira-meta-item select { margin-top: 4px; padding: 6px 10px; border: 1px solid #DFE1E6; border-radius: 6px; font-size: 12px; }
        .jira-issue-section { margin-bottom: 28px; }
        .jira-issue-section h3 { margin: 0 0 12px 0; color: #172B4D; font-size: 15px; font-weight: 600; }
        .jira-description { background: #F8F9FA; padding: 18px; border-radius: 10px; color: #172B4D; line-height: 1.6; font-size: 14px; }
        .jira-crm-links { display: flex; flex-direction: column; gap: 10px; }
        .jira-crm-link-item { display: flex; align-items: center; gap: 12px; padding: 14px; background: #F4F5F7; border-radius: 10px; }
        .jira-crm-link-item .link-type { background: linear-gradient(135deg, #0052CC 0%, #2684FF 100%); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .jira-crm-link-item .link-title { flex: 1; color: #172B4D; font-size: 14px; font-weight: 500; }
        .jira-crm-links .no-links { color: #5E6C84; font-style: italic; margin: 0; font-size: 14px; }
        .jira-comments { display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px; }
        .jira-comment { padding: 14px; background: #F8F9FA; border-radius: 10px; border-left: 4px solid #0052CC; }
        .jira-comment .comment-header { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .jira-comment .comment-header span { color: #5E6C84; }
        .jira-comment .comment-body { color: #172B4D; line-height: 1.5; font-size: 14px; }
        .jira-add-comment { display: flex; flex-direction: column; gap: 10px; }
        .jira-add-comment textarea { width: 100%; min-height: 80px; padding: 14px; border: 1px solid #DFE1E6; border-radius: 10px; resize: vertical; font-family: inherit; font-size: 14px; }
        .jira-add-comment textarea:focus { outline: none; border-color: #0052CC; box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2); }
        .jira-link-modal-content { max-width: 500px; padding: 24px; }
        .jira-link-form .form-group { margin-bottom: 20px; }
        .jira-link-form label { display: block; margin-bottom: 8px; font-weight: 600; color: #172B4D; font-size: 13px; }
        .jira-link-form select, .jira-link-form input { width: 100%; padding: 12px; border: 1px solid #DFE1E6; border-radius: 8px; font-size: 14px; }
        .jira-link-form select:focus, .jira-link-form input:focus { outline: none; border-color: #0052CC; box-shadow: 0 0 0 2px rgba(0, 82, 204, 0.2); }
        .link-record-results { max-height: 250px; overflow-y: auto; margin-top: 12px; }
        .link-record-item { display: flex; justify-content: space-between; align-items: center; padding: 14px; border: 1px solid #E8EAED; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
        .link-record-item:hover { background: #F4F5F7; border-color: #0052CC; }
        .link-record-item .record-title { color: #172B4D; font-weight: 500; }
        .link-record-item .record-status { color: #5E6C84; font-size: 12px; background: #F4F5F7; padding: 4px 10px; border-radius: 6px; }
        .jira-loading { text-align: center; padding: 40px; color: #5E6C84; }
        .jira-loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.8); display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: 8px; }
        .jira-notification { position: fixed; bottom: 20px; right: 20px; padding: 14px 20px; border-radius: 8px; color: #fff; font-weight: 500; opacity: 0; transform: translateY(20px); transition: all 0.3s; z-index: 10000; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .jira-notification.show { opacity: 1; transform: translateY(0); }
        .jira-notification-success { background: #36B37E; }
        .jira-notification-error { background: #FF5630; }
        .jira-notification-info { background: #0052CC; }
        
        /* Search dropdown styles */
        .search-dropdown .dropdown-item:hover { background: #f0f4ff; }
        .search-dropdown { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .assigned-item { transition: background 0.2s; }
        .assigned-item:hover { background: #e9ecef !important; }
        
        /* Sortable table headers */
        .sortable { cursor: pointer; user-select: none; position: relative; transition: background 0.2s; }
        .sortable:hover { background: #e9ecef; }
        .sortable .sort-arrow { margin-left: 5px; font-size: 0.75rem; color: #adb5bd; }
        .sortable.active-sort { background: #e7f1ff; }
        .sortable.active-sort .sort-arrow { color: #667eea; }
        .sortable.asc .sort-arrow::after { content: '▲'; }
        .sortable.desc .sort-arrow::after { content: '▼'; }
        
        /* Secondary owners multi-select */
        .secondary-owners-container { position: relative; }
        .secondary-owners-select { padding: 10px 15px; border: 2px solid #e1e5e9; border-radius: 8px; background: white; cursor: pointer; min-height: 44px; display: flex; align-items: center; }
        .secondary-owners-select:hover { border-color: #667eea; }
        .secondary-owners-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 1000; max-height: 250px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .secondary-owners-search { width: 100%; padding: 10px; border: none; border-bottom: 1px solid #e1e5e9; box-sizing: border-box; }
        .secondary-owners-list { max-height: 200px; overflow-y: auto; }
        .secondary-owner-option { padding: 10px 15px; cursor: pointer; display: flex !important; align-items: center; gap: 10px; transition: background 0.2s; width: 100%; box-sizing: border-box; }
        .secondary-owner-option:hover { background: #f0f4ff; }
        .secondary-owner-option.selected { background: #e7f1ff; }
        .secondary-owner-option input[type="checkbox"] { margin: 0; flex-shrink: 0; width: 16px; height: 16px; cursor: pointer; }
        .secondary-owner-option .owner-name { flex: 1; color: #333; font-size: 0.95rem; }
        .selected-secondary-owners { display: flex; flex-wrap: wrap; gap: 6px; }
        .secondary-owner-chip { display: inline-flex; align-items: center; gap: 6px; background: #e7f1ff; color: #667eea; padding: 4px 10px; border-radius: 15px; font-size: 0.85rem; }
        .secondary-owner-chip .remove-btn { cursor: pointer; font-weight: bold; margin-left: 4px; }
        .secondary-owner-chip .remove-btn:hover { color: #dc3545; }

        /* =====================================================
           OPPORTUNITY WORKSPACE STYLES (Shipley-based)
           ===================================================== */
        .opp-workspace-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f5f7fa;
            z-index: 2000;
            display: none;
            overflow-y: auto;
        }
        .opp-workspace-overlay.open { display: block; }
        
        .opp-workspace {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .opp-workspace-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .opp-workspace-header-left { display: flex; align-items: center; gap: 20px; }
        .opp-workspace-back {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #667eea;
            padding: 5px 10px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .opp-workspace-back:hover { background: #f0f0f0; }
        .opp-workspace-title h1 { margin: 0; font-size: 1.5rem; color: #333; }
        .opp-workspace-title p { margin: 5px 0 0 0; color: #666; font-size: 0.9rem; }
        .opp-workspace-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .opp-workspace-status.status-qualification { background: #e3f2fd; color: #1565c0; }
        .opp-workspace-status.status-capture { background: #fff3e0; color: #e65100; }
        .opp-workspace-status.status-bid_decision { background: #f3e5f5; color: #7b1fa2; }
        .opp-workspace-status.status-won { background: #e8f5e9; color: #2e7d32; }
        .opp-workspace-status.status-no_bid { background: #ffebee; color: #c62828; }
        
        .opp-workspace-header-right { display: flex; align-items: center; gap: 15px; }
        .opp-workspace-progress {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        .opp-workspace-progress-bar {
            width: 120px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        .opp-workspace-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s;
        }
        
        /* Phase Tabs */
        .opp-workspace-phases {
            display: flex;
            gap: 10px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .opp-phase-tab {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .opp-phase-tab:hover:not(.locked) { border-color: #667eea; }
        .opp-phase-tab.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15, #764ba215);
        }
        .opp-phase-tab.locked {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        .opp-phase-tab.completed {
            border-color: #28a745;
            background: #e8f5e9;
        }
        .opp-phase-tab .phase-icon { font-size: 1.5rem; margin-bottom: 5px; }
        .opp-phase-tab .phase-name { font-weight: 600; color: #333; font-size: 0.95rem; }
        .opp-phase-tab .phase-status { font-size: 0.8rem; color: #666; margin-top: 3px; }
        .opp-phase-tab.completed .phase-status { color: #28a745; }
        .opp-phase-tab.locked .phase-status { color: #999; }
        
        /* Phase Content */
        .opp-workspace-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            min-height: 500px;
        }
        .opp-phase-content { display: none; padding: 25px; }
        .opp-phase-content.active { display: block; }
        
        /* Section styling */
        .opp-section {
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        .opp-section:last-child { border-bottom: none; margin-bottom: 0; }
        .opp-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .opp-section-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .opp-section-header h3 .section-icon { font-size: 1.2rem; }
        
        /* Form grid */
        .opp-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .opp-form-group { margin-bottom: 15px; }
        .opp-form-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .opp-form-group input,
        .opp-form-group select,
        .opp-form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .opp-form-group input:focus,
        .opp-form-group select:focus,
        .opp-form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .opp-form-group textarea { resize: vertical; min-height: 80px; }
        .opp-form-group.full-width { grid-column: 1 / -1; }
        
        /* Scorecard */
        .opp-scorecard {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .opp-scorecard-row {
            display: grid;
            grid-template-columns: 1fr 100px 80px;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        .opp-scorecard-row:last-child { border-bottom: none; }
        .opp-scorecard-row.header {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            padding-bottom: 15px;
        }
        .opp-scorecard-criterion { color: #333; }
        .opp-scorecard-weight { text-align: center; color: #666; font-size: 0.9rem; }
        .opp-scorecard-score input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-weight: 600;
        }
        .opp-scorecard-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #667eea;
        }
        .opp-scorecard-total-label { font-weight: 600; font-size: 1.1rem; color: #333; }
        .opp-scorecard-total-value {
            font-size: 1.5rem;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 8px;
        }
        .opp-scorecard-total-value.score-high { background: #e8f5e9; color: #2e7d32; }
        .opp-scorecard-total-value.score-medium { background: #fff3e0; color: #e65100; }
        .opp-scorecard-total-value.score-low { background: #ffebee; color: #c62828; }
        
        /* Gate decision */
        .opp-gate-decision {
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }
        .opp-gate-decision h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
        }
        .opp-gate-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        .opp-gate-btn {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }
        .opp-gate-btn:hover { border-color: #667eea; }
        .opp-gate-btn.selected { border-color: #667eea; background: #667eea; color: white; }
        .opp-gate-btn.selected.pursue { background: #28a745; border-color: #28a745; }
        .opp-gate-btn.selected.monitor { background: #ffc107; border-color: #ffc107; color: #333; }
        .opp-gate-btn.selected.no-bid { background: #dc3545; border-color: #dc3545; }
        .opp-gate-btn .gate-icon { font-size: 1.5rem; margin-bottom: 5px; }
        .opp-gate-btn .gate-label { font-weight: 600; }
        
        /* Editable table */
        .opp-editable-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .opp-editable-table th,
        .opp-editable-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .opp-editable-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
        }
        .opp-editable-table tr:hover { background: #f8f9fa; }
        .opp-editable-table input,
        .opp-editable-table select,
        .opp-editable-table textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .opp-table-actions {
            display: flex;
            gap: 8px;
        }
        .opp-table-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .opp-table-btn.save { background: #28a745; color: white; }
        .opp-table-btn.delete { background: #dc3545; color: white; }
        .opp-table-btn.add { background: #667eea; color: white; }
        
        /* Win themes */
        .opp-win-theme {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .opp-win-theme-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .opp-win-theme-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .opp-win-theme-header input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-weight: 600;
        }
        
        /* Milestones checklist */
        .opp-milestones {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        .opp-milestone {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .opp-milestone:hover { background: #e8f4fd; }
        .opp-milestone.completed { background: #e8f5e9; }
        .opp-milestone input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
        }
        .opp-milestone-label { flex: 1; font-weight: 500; color: #333; }
        .opp-milestone.completed .opp-milestone-label { color: #28a745; }
        .opp-milestone-status { font-size: 1.2rem; }
        
        /* Action buttons row */
        .opp-workspace-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 20px 25px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            border-radius: 0 0 12px 12px;
        }
        .opp-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .opp-btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .opp-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .opp-btn-secondary { background: #e0e0e0; color: #333; }
        .opp-btn-secondary:hover { background: #d0d0d0; }
        .opp-btn-success { background: #28a745; color: white; }
        .opp-btn-success:hover { background: #218838; }
        .opp-btn-danger { background: #dc3545; color: white; }
        .opp-btn-danger:hover { background: #c82333; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .opp-workspace-header { flex-direction: column; gap: 15px; align-items: flex-start; }
            .opp-workspace-phases { flex-direction: column; }
            .opp-form-grid { grid-template-columns: 1fr; }
            .opp-scorecard-row { grid-template-columns: 1fr; gap: 8px; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-spacer"></div>
            <div class="header-logo"><img src="inteprosfedlogo.svg" alt="Logo"></div>
            <div class="header-user-info">
                <a href="profile.php" class="welcome-link">👤 Welcome, <b><?php echo htmlspecialchars($_SESSION["display_name"] ?? $_SESSION["username"]); ?></b> (<?php echo htmlspecialchars(ucfirst($_SESSION["role"])); ?>)</a>
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-secondary btn-small">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger btn-small">Logout</a>
            </div>
        </div>

        <nav class="nav-tabs">
            <button class="nav-tab active" data-tab-for="dashboard" onclick="showTab('dashboard', this)">Dashboard</button>
            <button class="nav-tab" data-tab-for="mytasks" data-permission="mytasks.view" onclick="showTab('mytasks', this)">My Tasks</button>
            <button class="nav-tab" data-tab-for="deptcalendar" onclick="showTab('deptcalendar', this)">Dept Calendar</button>
            <button class="nav-tab" data-tab-for="contact" onclick="showTab('contacts', this)">Organizations<br>& Contacts</button>
            <button class="nav-tab" data-tab-for="opportunity" onclick="showTab('opportunities', this)">Opportunities</button>
            <button class="nav-tab" data-tab-for="proposal" onclick="showTab('proposals', this)">Proposals</button>
            <button class="nav-tab" data-tab-for="event" onclick="showTab('events', this)">Events</button>
            <button class="nav-tab" data-tab-for="calendar" onclick="showTab('calendar', this)">Calendar</button>
            <button class="nav-tab" data-tab-for="kanban" onclick="showTab('kanban', this)">🌶️ Spicy Kanban</button>
            <button class="nav-tab" data-tab-for="task" onclick="showTab('tasks', this)">Tasks</button>
            <button class="nav-tab" data-tab-for="report" onclick="showTab('reports', this)">Jira</button>
        </nav>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <div class="dashboard-cards">
                <div class="dashboard-card" onclick="navigateToTab('contacts'); showContactSubTab('agencies');" title="View Agencies"><h3 id="totalAgencies">0</h3><p>Government Agencies</p></div>
                <div class="dashboard-card" onclick="navigateToTab('contacts');" title="View Contacts"><h3 id="totalContacts">0</h3><p>Active Contacts</p></div>
                <div class="dashboard-card" onclick="navigateToTab('opportunities');" title="View Opportunities"><h3 id="totalOpportunities">0</h3><p>Open Opportunities</p></div>
                <div class="dashboard-card" onclick="navigateToTab('proposals');" title="View Proposals"><h3 id="totalProposals">0</h3><p>Active Proposals</p></div>
            </div>
            <div class="stats-grid">
                <div class="stats-card clickable-card" onclick="navigateToTab('opportunities')" title="Click to view Opportunities">
                    <h3 style="margin: 0 0 15px 0; color: #333;">Opportunities by Agency <span style="font-size: 0.5em; color: #667eea; background: rgba(102,126,234,0.1); padding: 3px 10px; border-radius: 12px; margin-left: 8px;">→ View All</span></h3>
                    <canvas id="agencyChart"></canvas>
                </div>
                <div class="stats-card clickable-card" onclick="navigateToTab('proposals')" title="Click to view Proposals">
                    <h3 style="margin: 0 0 15px 0; color: #333;">Proposals by Agency <span style="font-size: 0.5em; color: #28a745; background: rgba(40,167,69,0.1); padding: 3px 10px; border-radius: 12px; margin-left: 8px;">→ View All</span></h3>
                    <canvas id="proposalAgencyChart"></canvas>
                </div>
            </div>
            <div class="stats-grid" style="margin-top: 20px;">
                <div class="stats-card clickable-card" onclick="navigateToTab('opportunities')" title="Click to view Opportunities">
                    <h3 style="margin: 0 0 15px 0; color: #333;">Opportunity Pipeline <span style="font-size: 0.5em; color: #667eea; background: rgba(102,126,234,0.1); padding: 3px 10px; border-radius: 12px; margin-left: 8px;">→ View All</span></h3>
                    <canvas id="pipelineChart"></canvas>
                </div>
                <div class="stats-card clickable-card" onclick="navigateToTab('proposals')" title="Click to view Proposals">
                    <h3 style="margin: 0 0 15px 0; color: #333;">Proposal Pipeline <span style="font-size: 0.5em; color: #28a745; background: rgba(40,167,69,0.1); padding: 3px 10px; border-radius: 12px; margin-left: 8px;">→ View All</span></h3>
                    <canvas id="proposalPipelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- My Tasks Tab -->
        <div id="mytasks" class="tab-content">
            <div style="margin-bottom: 20px;">
                <h2 style="margin: 0 0 5px 0; color: #333;">📋 My Tasks Dashboard</h2>
                <p style="margin: 0; color: #6c757d;">Your outstanding items and upcoming deadlines.</p>
            </div>
            <div class="dashboard-cards" id="myTasksSummary"></div>
            <div id="myTasksContent"></div>
        </div>

        <!-- Department Calendar Tab -->
        <div id="deptcalendar" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="margin: 0 0 5px 0; color: #333;">🗓️ Department Calendar</h2>
                    <p style="margin: 0; color: #6c757d;">Shared events visible to all team members. Click any date to add an event.</p>
                </div>
                <button class="btn" onclick="openDeptEventModal()">➕ Add Event</button>
            </div>
            
            <!-- Calendar Navigation -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button class="btn btn-secondary btn-small" onclick="navigateDeptCalendar(-1)">← Prev</button>
                    <button class="btn btn-secondary btn-small" onclick="navigateDeptCalendarToday()">Today</button>
                    <button class="btn btn-secondary btn-small" onclick="navigateDeptCalendar(1)">Next →</button>
                </div>
                <h3 id="deptCalendarTitle" style="margin: 0; color: #333;">December 2024</h3>
                <div style="display: flex; gap: 5px;">
                    <button class="btn btn-small dept-view-btn active" data-view="month" onclick="setDeptCalendarView('month')">Month</button>
                    <button class="btn btn-small btn-secondary dept-view-btn" data-view="week" onclick="setDeptCalendarView('week')">Week</button>
                    <button class="btn btn-small btn-secondary dept-view-btn" data-view="day" onclick="setDeptCalendarView('day')">Day</button>
                </div>
            </div>
            
            <!-- Calendar Grid -->
            <div id="deptCalendarGrid" class="dept-calendar-grid"></div>
            
            <!-- Legend -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                <span style="fontWeight: 600; color: #444;">Legend:</span>
                <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem;">
                    <span>🔄</span> Recurring Event
                </span>
                <span style="font-size: 0.85rem; color: #6c757d;">
                    Click any event to edit • Click a date to add new event
                </span>
            </div>
        </div>

        <!-- Agencies Tab -->
        <!-- Organizations Tab (formerly Contacts) -->
        <div id="contacts" class="tab-content">
            <!-- Sub-tabs for Federal Contacts, Agencies, Companies, Company Contacts -->
            <div class="sub-tabs">
                <button class="sub-tab active" onclick="showContactSubTab('federal')">👤 Federal Contacts</button>
                <button class="sub-tab" onclick="showContactSubTab('agencies')">🏛️ Agencies</button>
                <button class="sub-tab" onclick="showContactSubTab('companies')">🏢 Companies</button>
                <button class="sub-tab" onclick="showContactSubTab('companyContacts')">👥 Company Contacts</button>
            </div>
            
            <!-- Federal Contacts Sub-tab -->
            <div id="federalContactsSubTab" class="sub-tab-content active">
                <div style="margin-bottom: 20px;">
                    <h2 style="margin: 0 0 5px 0; color: #333;">Federal Contact Dashboard</h2>
                    <p style="margin: 0; color: #6c757d;">Track stakeholder contacts. Click on a contact name to view details.</p>
                </div>
                <div class="dashboard-cards" id="contactsSummary"></div>
                <div class="search-bar">
                    <select id="agencyFilter" onchange="filterContacts()">
                        <option value="ALL">All Agencies</option>
                    </select>
                    <input type="text" placeholder="Search contacts..." id="contactSearch">
                    <button class="btn" data-permission="contact.create" onclick="openModal('contactModal')">Add Contact</button>
                </div>
                <div id="contactsHierarchyView" class="contacts-hierarchy-view"></div>
            </div>
            
            <!-- Agencies Sub-tab -->
            <div id="agenciesSubTab" class="sub-tab-content">
                <div style="margin-bottom: 20px;">
                    <h2 style="margin: 0 0 5px 0; color: #333;">🏛️ Government Agencies</h2>
                    <p style="margin: 0; color: #6c757d;">Manage federal agencies and their divisions.</p>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Search agencies or divisions..." id="agencySearch" oninput="debouncedFilterAgencies()">
                    <button class="btn" data-permission="agency.create" onclick="openModal('agencyModal')">Add Agency</button>
                </div>
                <div id="agenciesHierarchyView" class="agencies-hierarchy"></div>
            </div>
            
            <!-- Companies Sub-tab -->
            <div id="companiesSubTab" class="sub-tab-content">
                <div style="margin-bottom: 20px;">
                    <h2 style="margin: 0 0 5px 0; color: #333;">🏢 Company Directory</h2>
                    <p style="margin: 0; color: #6c757d;">Manage partner, competitor, and vendor companies in the federal market.</p>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Search companies..." id="companySearch" oninput="debouncedFilterCompanies()">
                    <select id="companyTypeFilter" onchange="filterCompanies()">
                        <option value="ALL">All Types</option>
                        <option value="Prime Contractor">Prime Contractor</option>
                        <option value="Subcontractor">Subcontractor</option>
                        <option value="OEM / ISV">OEM / ISV</option>
                        <option value="Advisor / Consultant">Advisor / Consultant</option>
                    </select>
                    <select id="companyPostureFilter" onchange="filterCompanies()">
                        <option value="ALL">All Postures</option>
                        <option value="Partner">Partner</option>
                        <option value="Competitor">Competitor</option>
                        <option value="Partner-Competitor">Partner-Competitor</option>
                    </select>
                    <button class="btn" data-permission="contact.create" onclick="openModal('companyModal')">Add Company</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <th>Type</th>
                            <th>Parent Company</th>
                            <th>Strategic Value</th>
                            <th>Posture</th>
                            <th>Small Business</th>
                            <th>Vehicles</th>
                            <th>Contacts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTableBody"></tbody>
                </table>
            </div>
            
            <!-- Company Contacts Sub-tab -->
            <div id="companyContactsSubTab" class="sub-tab-content">
                <div style="margin-bottom: 20px;">
                    <h2 style="margin: 0 0 5px 0; color: #333;">👥 Company Contacts</h2>
                    <p style="margin: 0; color: #6c757d;">Track contacts at partner and competitor companies. Click on a contact name to view details.</p>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Search contacts..." id="companyContactSearch" oninput="debouncedFilterCompanyContacts()">
                    <select id="companyContactCompanyFilter" onchange="filterCompanyContacts()">
                        <option value="ALL">All Companies</option>
                    </select>
                    <select id="companyContactRoleFilter" onchange="filterCompanyContacts()">
                        <option value="ALL">All Roles</option>
                        <option value="Capture">Capture</option>
                        <option value="BD">BD</option>
                        <option value="Proposal">Proposal</option>
                        <option value="Technical Lead">Technical Lead</option>
                        <option value="Executive">Executive</option>
                    </select>
                    <button class="btn" data-permission="contact.create" onclick="openModal('companyContactModal')">Add Company Contact</button>
                </div>
                <table class="data-table" id="companyContactsTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Name</th>
                            <th style="width: 9%;">Title</th>
                            <th style="width: 10%;">Company</th>
                            <th style="width: 10%;">Primary Owner</th>
                            <th style="width: 12%;">Secondary Owners</th>
                            <th style="width: 8%;">Functional Role</th>
                            <th style="width: 8%;">Capture Role</th>
                            <th style="width: 15%;">Email</th>
                            <th style="width: 6%;">Status</th>
                            <th style="width: 7%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="companyContactsTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Opportunities Tab -->
        <div id="opportunities" class="tab-content">
            <div class="search-bar">
                <input type="text" placeholder="Search opportunities..." id="opportunitySearch">
                <select id="opportunityStatusFilter" class="kanban-select" onchange="populateOpportunitiesTable()" style="margin-left: 10px;">
                    <option value="all">All Statuses</option>
                    <option value="Lead">Lead</option>
                    <option value="Qualified">Qualified</option>
                    <option value="Capture" selected>Capture</option>
                    <option value="Proposal">Proposal</option>
                    <option value="Bid">Bid</option>
                    <option value="Won">Won</option>
                    <option value="Lost">Lost</option>
                    <option value="No bid">No bid</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Converted">Converted</option>
                </select>
                <button class="btn" data-permission="opportunity.create" onclick="openModal('opportunityModal')">Add Opportunity</button>
            </div>
            <table class="data-table" id="opportunitiesTable">
                <thead><tr>
                    <th class="sortable" data-sort="title" onclick="sortTable('opportunities', 'title')">Title <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="agencyName" onclick="sortTable('opportunities', 'agencyName')">Agency <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="division" onclick="sortTable('opportunities', 'division')">Division <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="ownerDisplayName" onclick="sortTable('opportunities', 'ownerDisplayName')">Owner <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="coOwnerDisplayName" onclick="sortTable('opportunities', 'coOwnerDisplayName')">Co-Owner <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="value" onclick="sortTable('opportunities', 'value')">Value <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="status" onclick="sortTable('opportunities', 'status')">Status <span class="sort-arrow"></span></th>
                    <th class="sortable active-sort" data-sort="dueDate" onclick="sortTable('opportunities', 'dueDate')">Due Date <span class="sort-arrow">▲</span></th>
                    <th class="sortable" data-sort="priority" onclick="sortTable('opportunities', 'priority')">Priority <span class="sort-arrow"></span></th>
                    <th>Actions</th>
                </tr></thead>
                <tbody id="opportunitiesTableBody"></tbody>
            </table>
        </div>

        <!-- Proposals Tab -->
        <div id="proposals" class="tab-content">
            <div class="search-bar">
                <input type="text" placeholder="Search proposals..." id="proposalSearch">
                <button class="btn" data-permission="proposal.create" onclick="openModal('proposalModal')">Add Proposal</button>
            </div>
            <table class="data-table" id="proposalsTable">
                <thead><tr>
                    <th class="sortable" data-sort="title" onclick="sortTable('proposals', 'title')">Title <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="agencyName" onclick="sortTable('proposals', 'agencyName')">Agency <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="ownerDisplayName" onclick="sortTable('proposals', 'ownerDisplayName')">Owner <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="value" onclick="sortTable('proposals', 'value')">Value <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="status" onclick="sortTable('proposals', 'status')">Status <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="submitDate" onclick="sortTable('proposals', 'submitDate')">Submit Date <span class="sort-arrow"></span></th>
                    <th class="sortable active-sort" data-sort="dueDate" onclick="sortTable('proposals', 'dueDate')">Due Date <span class="sort-arrow">▲</span></th>
                    <th class="sortable" data-sort="validity_date" onclick="sortTable('proposals', 'validity_date')">Validity <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="award_date" onclick="sortTable('proposals', 'award_date')">Award Date <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="winProbability" onclick="sortTable('proposals', 'winProbability')">Win % <span class="sort-arrow"></span></th>
                    <th>Actions</th>
                </tr></thead>
                <tbody id="proposalsTableBody"></tbody>
            </table>
        </div>

        <!-- Events Tab -->
        <div id="events" class="tab-content">
            <div class="section-header">
                <h2>📅 Events</h2>
                <button class="btn" data-permission="event.create" onclick="editEvent()">+ New Event</button>
            </div>
            <div class="filter-bar">
                <input type="text" id="eventSearch" placeholder="Search events..." oninput="debouncedFilterEvents()">
                <select id="eventStatusFilter" onchange="filterEventsTable()">
                    <option value="">All Statuses</option>
                    <option value="Planning">Planning</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <select id="eventTypeFilter" onchange="filterEventsTable()">
                    <option value="">All Types</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Conference">Conference</option>
                    <option value="Training">Training</option>
                    <option value="Webinar">Webinar</option>
                    <option value="Site Visit">Site Visit</option>
                    <option value="Review">Review</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <table class="data-table" id="eventsTable">
                <thead><tr>
                    <th style="width: 30px;"></th>
                    <th onclick="sortEventsTable('name')">Name <span class="sort-indicator" data-col="name"></span></th>
                    <th onclick="sortEventsTable('event_type')">Type <span class="sort-indicator" data-col="event_type"></span></th>
                    <th onclick="sortEventsTable('start_datetime')">Start <span class="sort-indicator" data-col="start_datetime"></span></th>
                    <th onclick="sortEventsTable('status')">Status <span class="sort-indicator" data-col="status"></span></th>
                    <th onclick="sortEventsTable('ownerDisplayName')">Owner <span class="sort-indicator" data-col="ownerDisplayName"></span></th>
                    <th>People</th>
                    <th>Tasks</th>
                    <th>Actions</th>
                </tr></thead>
                <tbody id="eventsTableBody"></tbody>
            </table>
        </div>

        <!-- Project Calendar Tab -->
        <div id="calendar" class="tab-content">
            <div class="calendar-header">
                <div class="calendar-title">
                    <h2>📅 Project Calendar</h2>
                    <p style="margin: 0; color: #6c757d;">View tasks, proposals, and opportunities by date</p>
                </div>
                <div class="calendar-controls">
                    <select id="calendarTypeFilter" class="calendar-select" onchange="refreshCalendar()">
                        <option value="ALL">All Event Types</option>
                        <option value="task">📋 Tasks Only</option>
                        <option value="proposal">📄 Proposals Only</option>
                        <option value="opportunity">💼 Opportunities Only</option>
                        <option value="event">📅 Events Only</option>
                    </select>
                    <select id="calendarProposalFilter" class="calendar-select" onchange="refreshCalendar()">
                        <option value="ALL">All Items</option>
                        <!-- Populated dynamically -->
                    </select>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="month" onclick="setCalendarView('month', this)">Month</button>
                        <button class="view-btn" data-view="week" onclick="setCalendarView('week', this)">Week</button>
                        <button class="view-btn" data-view="day" onclick="setCalendarView('day', this)">Day</button>
                    </div>
                </div>
            </div>
            
            <div class="calendar-navigation">
                <button class="nav-btn" onclick="navigateCalendar('prev')">‹ Previous</button>
                <div class="current-period" id="calendarPeriodLabel">December 2025</div>
                <button class="nav-btn" onclick="navigateCalendar('next')">Next ›</button>
                <button class="nav-btn today-btn" onclick="navigateCalendar('today')">Today</button>
            </div>
            
            <div id="calendarContainer" class="calendar-container">
                <!-- Calendar grid will be rendered here -->
            </div>
        </div>

        <!-- Spicy Kanban Tab -->
        <div id="kanban" class="tab-content">
            <div class="kanban-header">
                <div class="kanban-title">
                    <h2>🌶️ Spicy Kanban</h2>
                    <span style="color: #6c757d; font-size: 0.9rem;">Drag & drop to update status</span>
                </div>
                <div class="kanban-filters">
                    <!-- User Filter -->
                    <select id="kanbanUserFilter" class="kanban-select" onchange="updateKanbanItemFilter()">
                        <option value="all">👥 All Users</option>
                    </select>
                    
                    <!-- Date Range Filter -->
                    <select id="kanbanDateRange" class="kanban-select" onchange="updateKanbanDateRange()">
                        <option value="all">📅 All Dates</option>
                        <option value="day">Day</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                    </select>
                    
                    <!-- Date Navigation (hidden by default) -->
                    <div id="kanbanDateNav" class="kanban-date-nav" style="display: none;">
                        <button type="button" class="kanban-nav-btn" onclick="navigateKanbanDate(-1)">◀</button>
                        <span id="kanbanDateLabel" class="kanban-date-label">Today</span>
                        <button type="button" class="kanban-nav-btn" onclick="navigateKanbanDate(1)">▶</button>
                        <button type="button" class="kanban-nav-btn kanban-today-btn" onclick="navigateKanbanToday()">Today</button>
                    </div>
                    
                    <!-- Existing View Type Filters -->
                    <select id="kanbanViewType" class="kanban-select" onchange="updateKanbanItemFilter()">
                        <option value="all">View All Tasks</option>
                        <option value="proposal">View by Proposal</option>
                        <option value="contact">View by Contact</option>
                        <option value="opportunity">View by Opportunity</option>
                    </select>
                    <select id="kanbanItemFilter" class="kanban-select" style="display: none;" onchange="renderKanbanBoard()">
                        <option value="all">All Items</option>
                    </select>
                </div>
            </div>
            
            <div class="kanban-board" id="kanbanBoard">
                <div class="kanban-column backlog" data-status="To Do">
                    <div class="kanban-column-header">
                        <span>📥 To Do</span>
                        <span class="column-count" id="countToDo">0</span>
                    </div>
                    <div class="kanban-column-body" ondragover="handleDragOver(event)" ondrop="handleDrop(event, 'To Do')" ondragleave="handleDragLeave(event)">
                    </div>
                </div>
                
                <div class="kanban-column inprogress" data-status="In Progress">
                    <div class="kanban-column-header">
                        <span>🔄 In Progress</span>
                        <span class="column-count" id="countInProgress">0</span>
                    </div>
                    <div class="kanban-column-body" ondragover="handleDragOver(event)" ondrop="handleDrop(event, 'In Progress')" ondragleave="handleDragLeave(event)">
                    </div>
                </div>
                
                <div class="kanban-column done" data-status="Done">
                    <div class="kanban-column-header">
                        <span>✅ Done</span>
                        <span class="column-count" id="countDone">0</span>
                    </div>
                    <div class="kanban-column-body" ondragover="handleDragOver(event)" ondrop="handleDrop(event, 'Done')" ondragleave="handleDragLeave(event)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Tab -->
        <div id="tasks" class="tab-content">
            <div class="search-bar">
                <input type="text" placeholder="Search tasks..." id="taskSearch">
                <button class="btn" data-permission="task.create" onclick="openModal('taskModal')">Add Task</button>
            </div>
            <table class="data-table" id="tasksTable">
                <thead><tr>
                    <th class="sortable" data-sort="title" onclick="sortTable('tasks', 'title')">Task <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="relatedTo" onclick="sortTable('tasks', 'relatedTo')">Related To <span class="sort-arrow"></span></th>
                    <th class="sortable active-sort" data-sort="dueDate" onclick="sortTable('tasks', 'dueDate')">Due Date <span class="sort-arrow">▲</span></th>
                    <th class="sortable" data-sort="priority" onclick="sortTable('tasks', 'priority')">Priority <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="status" onclick="sortTable('tasks', 'status')">Status <span class="sort-arrow"></span></th>
                    <th class="sortable" data-sort="assignedToDisplayName" onclick="sortTable('tasks', 'assignedToDisplayName')">Assigned To <span class="sort-arrow"></span></th>
                    <th>Actions</th>
                </tr></thead>
                <tbody id="tasksTableBody"></tbody>
            </table>
        </div>

        <!-- Jira Tab -->
        <div id="reports" class="tab-content">
            <h2 style="margin-bottom: 20px;">Jira Integration</h2>
            <div class="stats-grid">
                <div class="stats-card"><h3>Opportunities by Agency</h3><canvas id="reportAgencyChart"></canvas></div>
                <div class="stats-card"><h3>Pipeline Status</h3><canvas id="reportPipelineChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Agency Modal -->
    <div id="agencyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('agencyModal')">&times;</span>
            <h2 id="agencyModalTitle">Add Agency</h2>
            <form id="agencyForm">
                <input type="hidden" id="agencyId">
                <div class="form-grid">
                    <div class="form-group"><label>Agency Name *</label><input type="text" id="agencyName" required></div>
                    <div class="form-group"><label>Type *</label><select id="agencyType" required><option value="">Select Type</option><option value="Federal">Federal</option><option value="State">State</option><option value="Local">Local</option><option value="Military">Military</option></select></div>
                    <div class="form-group"><label>Location *</label><input type="text" id="agencyLocation" required></div>
                    <div class="form-group"><label>Status *</label><select id="agencyStatus" required><option value="Active">Active</option><option value="Inactive">Inactive</option><option value="Pending">Pending</option></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="agencyDescription" rows="3"></textarea></div>
                <button type="submit" class="btn">Save Agency</button>
            </form>
        </div>
    </div>

    <!-- Contact Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('contactModal')">&times;</span>
            <h2 id="contactModalTitle">Add Contact</h2>
            <form id="contactForm">
                <input type="hidden" id="contactId">
                <div class="form-grid">
                    <div class="form-group"><label>First Name *</label><input type="text" id="contactFirstName" required></div>
                    <div class="form-group"><label>Last Name *</label><input type="text" id="contactLastName" required></div>
                    <div class="form-group"><label>Title/Role *</label><input type="text" id="contactTitle" required></div>
                    <div class="form-group"><label>Agency *</label><select id="contactAgency" required onchange="loadDivisionsForAgency('contact')"></select></div>
                    <div class="form-group">
                        <label>Division <span style="font-weight: normal; color: #6c757d; font-size: 0.85rem;">(select or type new)</span></label>
                        <input type="text" id="contactDivision" list="contactDivisionList" placeholder="Select or enter division...">
                        <datalist id="contactDivisionList"></datalist>
                    </div>
                    <div class="form-group"><label>Owner</label><select id="contactOwner"></select></div>
                    <div class="form-group"><label>Email *</label><input type="email" id="contactEmail" required></div>
                    <div class="form-group"><label>Phone</label><input type="tel" id="contactPhone"></div>
                    <div class="form-group"><label>Status *</label><select id="contactStatus" required><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                </div>
                <div class="form-group"><label>Notes</label><textarea id="contactNotes" rows="3"></textarea></div>
                <button type="submit" class="btn">Save Contact</button>
            </form>
        </div>
    </div>

    <!-- Contact Detail Modal -->
    <div id="contactDetailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('contactDetailModal')">&times;</span>
            <div id="contactDetailContent"></div>
        </div>
    </div>

    <!-- Opportunity Modal -->
    <div id="opportunityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('opportunityModal')">&times;</span>
            <h2 id="opportunityModalTitle">Add Opportunity</h2>
            <form id="opportunityForm">
                <input type="hidden" id="opportunityId">
                <div class="form-grid">
                    <div class="form-group"><label>Title *</label><input type="text" id="opportunityTitle" required></div>
                    <div class="form-group"><label>Agency *</label><select id="opportunityAgency" required onchange="loadDivisionsForAgency('opportunity')"></select></div>
                    <div class="form-group">
                        <label>Division <span style="font-weight: normal; color: #6c757d; font-size: 0.85rem;">(select or type new)</span></label>
                        <input type="text" id="opportunityDivision" list="opportunityDivisionList" placeholder="Select or enter division...">
                        <datalist id="opportunityDivisionList"></datalist>
                    </div>
                    <div class="form-group"><label>Owner</label><select id="opportunityOwner"></select></div>
                    <div class="form-group">
                        <label>Co-Owner Type</label>
                        <select id="opportunityCoOwnerType" onchange="loadCoOwnerOptions()">
                            <option value="">No Co-Owner</option>
                            <option value="user">👤 User</option>
                            <option value="federal">🏛️ Federal Contact</option>
                            <option value="commercial">🏢 Commercial Contact</option>
                        </select>
                    </div>
                    <div class="form-group" id="coOwnerSelectGroup" style="display: none;">
                        <label>Co-Owner</label>
                        <select id="opportunityCoOwner">
                            <option value="">Select Co-Owner</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Value ($) *</label><input type="number" id="opportunityValue" required></div>
                    <div class="form-group"><label>Status *</label><select id="opportunityStatus" required onchange="handleOpportunityStatusChange()"><option>Lead</option><option>Qualified</option><option>Capture</option><option>Proposal</option><option>Bid</option><option>Won</option><option>Lost</option><option>No bid</option><option>Cancelled</option></select></div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <div class="date-tbd-container">
                            <input type="date" id="opportunityDueDate">
                            <label class="tbd-toggle">
                                <input type="checkbox" id="opportunityDueDateTBD" onchange="toggleOpportunityTBD()">
                                <span class="tbd-label">TBD</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group"><label>Priority *</label><select id="opportunityPriority" required><option>Low</option><option>Medium</option><option>High</option></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="opportunityDescription" rows="3"></textarea></div>
                
                <!-- Linked Contacts Section (only visible when editing) -->
                <div id="opportunityContactsSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">👥 Linked Contacts</h4>
                        <button type="button" class="btn" onclick="openAddContactToOpportunityModal()">+ Add Contact</button>
                    </div>
                    <div id="opportunityLinkedContacts" class="linked-list" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Linked contacts will be rendered here -->
                    </div>
                </div>
                
                <!-- Documents Section (only visible when editing) -->
                <div id="opportunityDocumentsSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">📎 Documents</h4>
                        <label class="btn btn-small" style="margin: 0; cursor: pointer;">
                            + Upload Document
                            <input type="file" id="opportunityDocumentUpload" style="display: none;" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip"
                                   onchange="uploadOpportunityDocument()">
                        </label>
                    </div>
                    <div id="opportunityDocumentsList" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Documents will be rendered here -->
                    </div>
                </div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">Save Opportunity</button>
            </form>
        </div>
    </div>

    <!-- Convert Opportunity to Proposal Modal -->
    <div id="convertToProposalModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="cancelConvertToProposal()">&times;</span>
            <h2>📋 Convert to Proposal</h2>
            <p style="color: #6c757d; margin-bottom: 20px;">This opportunity will be converted to a proposal. The original opportunity will be marked as "Converted" for historical tracking.</p>
            
            <div id="convertOpportunityInfo" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <!-- Opportunity details will be shown here -->
            </div>
            
            <form id="convertToProposalForm" onsubmit="submitConvertToProposal(event)">
                <input type="hidden" id="convertOpportunityId">
                
                <div class="form-group">
                    <label>Win Probability (%) *</label>
                    <input type="number" id="convertWinProbability" min="0" max="100" value="50" required>
                </div>
                
                <div class="form-group">
                    <label>Submit Date *</label>
                    <input type="date" id="convertSubmitDate" required>
                </div>
                
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" id="convertDueDate">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="cancelConvertToProposal()">Cancel</button>
                    <button type="submit" class="btn">Convert to Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Proposal Modal -->
    <div id="proposalModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('proposalModal')">&times;</span>
            <h2 id="proposalModalTitle">Add Proposal</h2>
            <form id="proposalForm">
                <input type="hidden" id="proposalId">
                <div class="form-grid">
                    <div class="form-group"><label>Title *</label><input type="text" id="proposalTitle" required></div>
                    <div class="form-group"><label>Agency *</label><select id="proposalAgency" required></select></div>
                    <div class="form-group"><label>Owner</label><select id="proposalOwner"></select></div>
                    <div class="form-group"><label>Value ($) *</label><input type="number" id="proposalValue" required></div>
                    <div class="form-group"><label>Status *</label><select id="proposalStatus" required><option>Draft</option><option>Submitted</option><option>Under Review</option><option>Won</option><option>Lost</option><option>No-Bid</option><option>Canceled</option></select></div>
                    <div class="form-group"><label>Submit Date</label><input type="date" id="proposalSubmitDate"></div>
                    <div class="form-group"><label>Due Date *</label><input type="date" id="proposalDueDate" required></div>
                    <div class="form-group">
                        <label>Validity Date</label>
                        <input type="date" id="proposalValidityDate" title="Date until which proposal remains valid">
                    </div>
                    <div class="form-group">
                        <label>Award Date</label>
                        <input type="date" id="proposalAwardDate" title="Date when contract was awarded">
                    </div>
                    <div class="form-group"><label>Win Probability (%) *</label><input type="number" id="proposalWinProbability" min="0" max="100" required></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="proposalDescription" rows="3"></textarea></div>
                
                <!-- Linked Contacts Section (only visible when editing) -->
                <div id="proposalContactsSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">👥 Linked Contacts</h4>
                        <button type="button" class="btn" onclick="openAddContactToProposalModal()">+ Add Contact</button>
                    </div>
                    <div id="proposalLinkedContacts" class="linked-list" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Linked contacts will be rendered here -->
                    </div>
                </div>
                
                <!-- Documents Section (only visible when editing) -->
                <div id="proposalDocumentsSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">📎 Documents</h4>
                        <label class="btn btn-small" style="margin: 0; cursor: pointer;">
                            + Upload Document
                            <input type="file" id="proposalDocumentUpload" style="display: none;" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip"
                                   onchange="uploadProposalDocument()">
                        </label>
                    </div>
                    <div id="proposalDocumentsList" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Documents will be rendered here -->
                    </div>
                </div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">Save Proposal</button>
            </form>
        </div>
    </div>

    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('eventModal')">&times;</span>
            <h2 id="eventModalTitle">Add Event</h2>
            <form id="eventForm">
                <input type="hidden" id="eventId">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;"><label>Event Name *</label><input type="text" id="eventName" required></div>
                    <div class="form-group"><label>Event Type *</label>
                        <select id="eventType" required>
                            <option value="Meeting">Meeting</option>
                            <option value="Conference">Conference</option>
                            <option value="Training">Training</option>
                            <option value="Webinar">Webinar</option>
                            <option value="Site Visit">Site Visit</option>
                            <option value="Review">Review</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Status *</label>
                        <select id="eventStatus" required>
                            <option value="Planning">Planning</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Start Date/Time *</label><input type="datetime-local" id="eventStartDatetime" required></div>
                    <div class="form-group"><label>End Date/Time</label><input type="datetime-local" id="eventEndDatetime"></div>
                    <div class="form-group"><label>Location</label><input type="text" id="eventLocation" placeholder="Physical address or room"></div>
                    <div class="form-group"><label>Virtual Link</label><input type="url" id="eventVirtualLink" placeholder="https://zoom.us/..."></div>
                    <div class="form-group"><label>Priority</label>
                        <select id="eventPriority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Owner</label><select id="eventOwner"></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="eventDescription" rows="3" placeholder="Event details, agenda, notes..."></textarea></div>
                
                <!-- Attendees Section - Always Visible -->
                <div id="eventAttendeesSection" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">🎟️ Attendees</h4>
                        <button type="button" class="btn btn-small" onclick="openUnifiedAttendeePicker()">+ Add Attendee</button>
                    </div>
                    <div id="eventAttendeesList" class="linked-list" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No attendees added yet. Click "Add Attendee" to add people.</p>
                    </div>
                </div>
                
                <!-- Tasks Section - Always Visible -->
                <div id="eventTasksSection" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">📋 Tasks</h4>
                        <div style="display: flex; gap: 5px;">
                            <button type="button" class="btn btn-small btn-secondary" onclick="openLinkTaskToEventModal()">🔗 Link Existing</button>
                            <button type="button" class="btn btn-small" onclick="openNewTaskForEventModal()">+ New Task</button>
                        </div>
                    </div>
                    <div id="eventTasksList" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No tasks linked yet. Create a new task or link existing ones.</p>
                    </div>
                </div>
                
                <!-- Notes Section - Always Visible -->
                <div id="eventNotesSection" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">📝 Notes</h4>
                        <button type="button" class="btn btn-small" onclick="openAddEventNoteInModal()">+ Add Note</button>
                    </div>
                    <div id="eventModalNotesList" style="margin-top: 10px; max-height: 250px; overflow-y: auto;">
                        <p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No notes yet. Add notes to track discussions and decisions.</p>
                    </div>
                </div>
                
                <!-- Documents Section - Only visible when editing (needs event ID) -->
                <div id="eventDocumentsSection" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <div class="linked-header">
                        <h4 style="margin: 0; color: #667eea;">📎 Documents</h4>
                        <label class="btn btn-small" style="margin: 0; cursor: pointer;">
                            + Upload Document
                            <input type="file" id="eventDocumentUpload" style="display: none;" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip"
                                   onchange="uploadEventDocument()">
                        </label>
                    </div>
                    <div id="eventDocumentsList" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Documents will be rendered here -->
                    </div>
                </div>
                
                <button type="submit" class="btn" id="eventSaveBtn" style="margin-top: 20px;">Save Event</button>
            </form>
        </div>
    </div>

    <!-- View Event Modal (Read-Only) -->
    <div id="viewEventModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('viewEventModal')">&times;</span>
            <h2 id="viewEventModalTitle">View Event</h2>
            <div id="viewEventContent">
                <!-- Event details will be rendered here -->
            </div>
            <div id="viewEventActions" style="margin-top: 20px; display: flex; gap: 10px;">
                <!-- Buttons will be rendered based on permissions -->
            </div>
        </div>
    </div>

    <!-- Add Event Note Modal -->
    <div id="addEventNoteInModalDialog" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('addEventNoteInModalDialog')">&times;</span>
            <h2>📝 Add Note</h2>
            <form id="eventNoteInModalForm">
                <div class="form-group">
                    <label>Interaction Type</label>
                    <select id="eventNoteInModalType">
                        <option value="General">General</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Phone Call">Phone Call</option>
                        <option value="Email">Email</option>
                        <option value="Decision">Decision</option>
                        <option value="Action Item">Action Item</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note *</label>
                    <textarea id="eventNoteInModalText" rows="5" required placeholder="Enter your note..."></textarea>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addEventNoteInModalDialog')">Cancel</button>
                    <button type="submit" class="btn">Save Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unified Attendee Picker Modal -->
    <div id="unifiedAttendeeModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('unifiedAttendeeModal')">&times;</span>
            <h2>🎟️ Add Attendees</h2>
            
            <!-- Search and Filter -->
            <div style="margin-bottom: 15px;">
                <input type="text" id="attendeeSearchInput" placeholder="Search by name..." 
                       style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 1rem;"
                       oninput="filterAttendeeList()">
            </div>
            
            <!-- Type Filter Tabs -->
            <div style="display: flex; gap: 5px; margin-bottom: 15px;">
                <button type="button" class="btn btn-small attendee-filter-btn active" data-filter="all" onclick="setAttendeeFilter('all')">All</button>
                <button type="button" class="btn btn-small btn-secondary attendee-filter-btn" data-filter="user" onclick="setAttendeeFilter('user')">👤 Users</button>
                <button type="button" class="btn btn-small btn-secondary attendee-filter-btn" data-filter="federal" onclick="setAttendeeFilter('federal')">🏛️ Federal</button>
                <button type="button" class="btn btn-small btn-secondary attendee-filter-btn" data-filter="commercial" onclick="setAttendeeFilter('commercial')">🏢 Commercial</button>
            </div>
            
            <!-- Attendee List -->
            <div id="attendeePickerList" style="max-height: 350px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px;">
                <!-- Attendee options will be rendered here -->
            </div>
            
            <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                <span id="attendeeSelectedCount" style="color: #6c757d;">0 selected</span>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('unifiedAttendeeModal')">Cancel</button>
                    <button type="button" class="btn" onclick="addSelectedAttendees()">Add Selected</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Link Existing Task to Event Modal -->
    <div id="linkTaskToEventModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('linkTaskToEventModal')">&times;</span>
            <h2>🔗 Link Existing Task</h2>
            
            <div style="margin-bottom: 15px;">
                <input type="text" id="linkTaskSearchInput" placeholder="Search tasks..." 
                       style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 1rem;"
                       oninput="filterLinkTaskList()">
            </div>
            
            <div id="linkTaskList" style="max-height: 350px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px;">
                <!-- Available tasks will be rendered here -->
            </div>
            
            <div style="margin-top: 15px; text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('linkTaskToEventModal')">Cancel</button>
                <button type="button" class="btn" onclick="linkSelectedTasksToEvent()">Link Selected</button>
            </div>
        </div>
    </div>

    <!-- New Task for Event Modal -->
    <div id="newTaskForEventModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('newTaskForEventModal')">&times;</span>
            <h2>📋 New Task for Event</h2>
            <div id="newTaskEventName" style="background: #f0f4ff; padding: 10px; border-radius: 6px; margin-bottom: 15px; color: #667eea; font-weight: 500;"></div>
            
            <form id="newTaskForEventForm">
                <div class="form-group"><label>Task Title *</label><input type="text" id="newEventTaskTitle" required></div>
                <div class="form-grid">
                    <div class="form-group"><label>Status</label>
                        <select id="newEventTaskStatus">
                            <option value="To Do">To Do</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Done">Done</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Priority</label>
                        <select id="newEventTaskPriority">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Due Date</label><input type="date" id="newEventTaskDueDate"></div>
                    <div class="form-group"><label>Assignee</label><select id="newEventTaskAssignee"></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="newEventTaskDescription" rows="3" placeholder="Task details..."></textarea></div>
                
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('newTaskForEventModal')">Cancel</button>
                    <button type="submit" class="btn">Create Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Event People Assignment Modal (legacy - for backwards compatibility) -->
    <div id="eventPeopleModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" onclick="closeModal('eventPeopleModal')">&times;</span>
            <h2>👥 Manage Event People</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Users Column -->
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #667eea;">👤 CRM Users</h4>
                    <div id="eventUsersList" style="max-height: 300px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px;">
                        <!-- User checkboxes will be rendered here -->
                    </div>
                </div>
                
                <!-- Federal Contacts Column -->
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #28a745;">🏛️ Federal Contacts</h4>
                    <input type="text" id="eventFedContactSearch" placeholder="Search federal contacts..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #e0e0e0; border-radius: 6px; box-sizing: border-box;" onkeyup="filterEventFedContacts()">
                    <div id="eventFedContactsList" style="max-height: 250px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px;">
                        <!-- Federal contact checkboxes will be rendered here -->
                    </div>
                </div>
                
                <!-- Commercial Contacts Column -->
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #fd7e14;">🏢 Commercial Contacts</h4>
                    <input type="text" id="eventCommContactSearch" placeholder="Search commercial contacts..." style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #e0e0e0; border-radius: 6px; box-sizing: border-box;" onkeyup="filterEventCommContacts()">
                    <div id="eventCommContactsList" style="max-height: 250px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px;">
                        <!-- Commercial contact checkboxes will be rendered here -->
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('eventPeopleModal')">Cancel</button>
                <button type="button" class="btn" onclick="saveEventPeopleAssignments()">Save Assignments</button>
            </div>
        </div>
    </div>

    <!-- Task Modal -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('taskModal')">&times;</span>
            <h2 id="taskModalTitle">Add Task</h2>
            <form id="taskForm">
                <input type="hidden" id="taskId">
                <input type="hidden" id="taskRelatedItemId">
                <input type="hidden" id="taskRelatedContactType">
                <input type="hidden" id="taskWorkspacePhase">
                <div class="form-grid">
                    <div class="form-group"><label>Task Title *</label><input type="text" id="taskTitle" required></div>
                    <div class="form-group"><label>Related To *</label><select id="taskRelatedTo" required onchange="updateRelatedItemOptions()"><option value="">Select Type</option><option value="Agency">Agency</option><option value="Contact">Contact</option><option value="Opportunity">Opportunity</option><option value="Proposal">Proposal</option><option value="Event">Event</option></select></div>
                    <div class="form-group" id="relatedItemGroup" style="display: none;">
                        <label>Select <span id="relatedItemLabel">Item</span> *</label>
                        <div class="search-select-container">
                            <input type="text" id="relatedItemSearch" placeholder="Search..." oninput="filterRelatedItems()" onfocus="showRelatedItemDropdown()">
                            <div class="search-select-dropdown" id="relatedItemDropdown"></div>
                        </div>
                        <div id="selectedRelatedItem" class="selected-item-display" style="display: none;"></div>
                    </div>
                    <div class="form-group"><label>Due Date *</label><input type="date" id="taskDueDate" required></div>
                    <div class="form-group"><label>Priority *</label><select id="taskPriority" required><option>Low</option><option>Medium</option><option>High</option></select></div>
                    <div class="form-group"><label>Status *</label><select id="taskStatus" required><option>To Do</option><option>In Progress</option><option>Done</option></select></div>
                    <div class="form-group"><label>Assigned To</label><select id="taskAssignedTo"></select></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="taskDescription" rows="3"></textarea></div>
                <button type="submit" class="btn">Save Task</button>
            </form>
        </div>
    </div>

    <!-- Company Modal -->
    <div id="companyModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <span class="close" onclick="closeModal('companyModal')">&times;</span>
            <h2 id="companyModalTitle">Add Company</h2>
            <form id="companyForm">
                <input type="hidden" id="companyId">
                
                <h3 style="margin: 0 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">🏢 Company Identity</h3>
                <div class="form-grid">
                    <div class="form-group"><label>Company Name *</label><input type="text" id="companyName" required></div>
                    <div class="form-group"><label>Company Type</label>
                        <select id="companyType">
                            <option value="">Select Type</option>
                            <option value="Prime Contractor">Prime Contractor</option>
                            <option value="Subcontractor">Subcontractor</option>
                            <option value="OEM / ISV">OEM / ISV</option>
                            <option value="Advisor / Consultant">Advisor / Consultant</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Parent Company</label>
                        <div class="search-select-container">
                            <input type="text" id="parentCompanySearch" placeholder="Search or type new company..." oninput="filterParentCompanies()" onfocus="showParentCompanyDropdown()">
                            <div class="search-select-dropdown" id="parentCompanyDropdown"></div>
                        </div>
                        <input type="hidden" id="parentCompanyId">
                        <div id="selectedParentCompany" class="selected-item-display" style="display: none;"></div>
                        <div id="parentCompanyPrompt" class="parent-company-prompt" style="display: none;">
                            <h4>🆕 Company not found</h4>
                            <p>Would you like to add "<span id="newParentCompanyName"></span>" as a new company?</p>
                            <div class="parent-company-prompt-buttons">
                                <button type="button" class="btn" onclick="createParentCompany()">Yes, Create Company</button>
                                <button type="button" class="btn btn-secondary" onclick="cancelParentCompanyPrompt()">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"><label>Website</label><input type="url" id="companyWebsite" placeholder="https://"></div>
                </div>
                <div class="form-group"><label>Description</label><textarea id="companyDescription" rows="2"></textarea></div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">🏛️ Federal Market Position</h3>
                <div class="form-group">
                    <label>Core Federal Customers (Search and select agencies)</label>
                    <div class="agency-select-container">
                        <input type="text" class="agency-select-search" id="companyCoreCustomerSearch" placeholder="Search agencies..." oninput="filterCompanyCoreCustomers()">
                        <div id="companyCoreCustomerList"></div>
                    </div>
                    <div id="companySelectedCoreCustomers" class="company-contact-agencies-list" style="margin-top: 10px;"></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Primary NAICS Codes</label><input type="text" id="companyNaicsCodes" placeholder="e.g., 541512, 541519"></div>
                    <div class="form-group"><label>UEI</label><input type="text" id="companyUei" maxlength="12" placeholder="12-character UEI"></div>
                    <div class="form-group"><label>CAGE Code</label><input type="text" id="companyCageCode" maxlength="10" placeholder="5-character CAGE"></div>
                </div>
                
                <div class="form-group">
                    <label>Small Business Status (Select all that apply)</label>
                    <div class="checkbox-group" id="smallBusinessCheckboxes">
                        <label class="checkbox-item"><input type="checkbox" name="smallBusinessStatus" value="8(a)"> <span>8(a)</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="smallBusinessStatus" value="SDVOSB"> <span>SDVOSB</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="smallBusinessStatus" value="HUBZone"> <span>HUBZone</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="smallBusinessStatus" value="WOSB"> <span>WOSB</span></label>
                    </div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">📋 Contract Vehicles & Access</h3>
                <div class="form-group">
                    <label>Vehicles Held (Select from list or add custom)</label>
                    <div class="checkbox-group" id="vehiclesCheckboxes">
                        <label class="checkbox-item"><input type="checkbox" name="vehicles" value="GSA MAS" onchange="updateVehiclesTags()"> <span>GSA MAS</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="vehicles" value="SeaPort-NxG" onchange="updateVehiclesTags()"> <span>SeaPort-NxG</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="vehicles" value="CIO-SP4" onchange="updateVehiclesTags()"> <span>CIO-SP4</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="vehicles" value="OASIS Plus" onchange="updateVehiclesTags()"> <span>OASIS Plus</span></label>
                        <label class="checkbox-item"><input type="checkbox" name="vehicles" value="Agency-Specific IDIQs" onchange="updateVehiclesTags()"> <span>Agency-Specific IDIQs</span></label>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <input type="text" id="customVehicleInput" placeholder="Add custom vehicle..." style="flex: 1; padding: 8px 12px; border: 2px solid #e1e5e9; border-radius: 6px;" onkeypress="if(event.key==='Enter'){event.preventDefault();addCustomVehicle();}">
                        <button type="button" onclick="addCustomVehicle()" class="btn" style="padding: 8px 15px;">+ Add</button>
                    </div>
                    <div id="selectedVehiclesTags" class="company-contact-agencies-list" style="margin-top: 10px;"></div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">⭐ Strategic Value</h3>
                <div class="form-grid">
                    <div class="form-group"><label>Strategic Importance</label>
                        <select id="companyStrategicImportance">
                            <option value="">Select Importance</option>
                            <option value="Opportunistic">Opportunistic</option>
                            <option value="Strategic">Strategic</option>
                            <option value="Anchor Partner">Anchor Partner</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Competitive Posture</label>
                        <select id="companyCompetitivePosture">
                            <option value="">Select Posture</option>
                            <option value="Partner">Partner</option>
                            <option value="Competitor">Competitor</option>
                            <option value="Partner-Competitor">Partner-Competitor</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select id="companyStatus" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">Save Company</button>
            </form>
        </div>
    </div>

    <!-- Company Contact Modal -->
    <div id="companyContactModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('companyContactModal')">&times;</span>
            <h2 id="companyContactModalTitle">Add Company Contact</h2>
            <form id="companyContactForm">
                <input type="hidden" id="companyContactId">
                
                <h3 style="margin: 0 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">👤 Identity & Role</h3>
                <div class="form-grid">
                    <div class="form-group"><label>First Name *</label><input type="text" id="ccFirstName" required></div>
                    <div class="form-group"><label>Last Name *</label><input type="text" id="ccLastName" required></div>
                    <div class="form-group"><label>Title</label><input type="text" id="ccTitle"></div>
                    <div class="form-group"><label>Functional Role</label>
                        <select id="ccFunctionalRole">
                            <option value="">Select Role</option>
                            <option value="Capture">Capture</option>
                            <option value="BD">BD</option>
                            <option value="Proposal">Proposal</option>
                            <option value="Technical Lead">Technical Lead</option>
                            <option value="Executive">Executive</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;"><label>Company *</label>
                        <div class="search-select-container">
                            <input type="text" id="ccCompanySearch" placeholder="Search or type new company..." oninput="filterCCCompanies()" onfocus="showCCCompanyDropdown()">
                            <div class="search-select-dropdown" id="ccCompanyDropdown"></div>
                        </div>
                        <input type="hidden" id="ccCompanyId" required>
                        <div id="selectedCCCompany" class="selected-item-display" style="display: none;"></div>
                        <div id="ccCompanyPrompt" class="parent-company-prompt" style="display: none;">
                            <h4>🆕 Company not found</h4>
                            <p>Would you like to add "<span id="newCCCompanyName"></span>" as a new company?</p>
                            <div class="parent-company-prompt-buttons">
                                <button type="button" class="btn" onclick="createCCCompany()">Yes, Create Company</button>
                                <button type="button" class="btn btn-secondary" onclick="cancelCCCompanyPrompt()">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">👑 Ownership</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Primary Owner</label>
                        <select id="ccPrimaryOwner">
                            <option value="">Select Primary Owner</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Secondary Owner(s)</label>
                        <div class="secondary-owners-container">
                            <div class="secondary-owners-select" id="ccSecondaryOwnersSelect" onclick="toggleSecondaryOwnersDropdown()">
                                <span id="ccSecondaryOwnersPlaceholder">Select Secondary Owners...</span>
                            </div>
                            <div class="secondary-owners-dropdown" id="ccSecondaryOwnersDropdown" style="display: none;">
                                <input type="text" class="secondary-owners-search" id="ccSecondaryOwnersSearch" placeholder="Search users..." oninput="filterSecondaryOwners()" onclick="event.stopPropagation()">
                                <div id="ccSecondaryOwnersList" class="secondary-owners-list"></div>
                            </div>
                        </div>
                        <div id="ccSelectedSecondaryOwners" class="selected-secondary-owners" style="margin-top: 8px;"></div>
                    </div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">🎯 Federal Capture Influence</h3>
                <div class="form-grid">
                    <div class="form-group"><label>Capture Role</label>
                        <select id="ccCaptureRole">
                            <option value="">Select Role</option>
                            <option value="Prime Capture Lead">Prime Capture Lead</option>
                            <option value="Sub Capture Lead">Sub Capture Lead</option>
                            <option value="Pricing Lead">Pricing Lead</option>
                            <option value="Solution Architect">Solution Architect</option>
                            <option value="Exec Sponsor">Exec Sponsor</option>
                        </select>
                    </div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">🏛️ Federal Experience</h3>
                <div class="form-group">
                    <label>Agencies Supported (Search and select)</label>
                    <div class="agency-select-container">
                        <input type="text" class="agency-select-search" id="ccAgencySearch" placeholder="Search agencies..." oninput="filterCCAgencies()">
                        <div id="ccAgencyList"></div>
                    </div>
                    <div id="ccSelectedAgencies" class="company-contact-agencies-list" style="margin-top: 10px;"></div>
                </div>
                
                <h3 style="margin: 20px 0 15px 0; color: #667eea; font-size: 1.1rem; border-bottom: 2px solid #667eea; padding-bottom: 8px;">📞 Contact Information</h3>
                <div class="form-grid">
                    <div class="form-group"><label>Email</label><input type="email" id="ccEmail"></div>
                    <div class="form-group"><label>Phone</label><input type="tel" id="ccPhone"></div>
                    <div class="form-group"><label>Status</label>
                        <select id="ccStatus" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Notes</label><textarea id="ccNotes" rows="2"></textarea></div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">Save Contact</button>
            </form>
        </div>
    </div>

    <!-- Company Contact Detail Panel (Slide-out) -->
    <div class="contact-panel-overlay" id="companyContactPanelOverlay" onclick="closeCompanyContactPanel()"></div>
    <div class="contact-panel" id="companyContactPanel">
        <button class="contact-panel-close" onclick="closeCompanyContactPanel()">×</button>
        <div class="contact-panel-header">
            <h3 id="ccPanelName">Contact Name</h3>
            <p id="ccPanelTitle">Title - Company</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchCompanyContactTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchCompanyContactTab('notes')">Notes</button>
            <button class="contact-panel-tab" onclick="switchCompanyContactTab('linked')">Linked</button>
        </div>
        <div id="ccInfoSection" class="contact-panel-section">
            <!-- Company contact info will be rendered here -->
        </div>
        <div id="ccNotesSection" class="contact-panel-section" style="display: none;">
            <div class="notes-header">
                <h4 style="margin: 0;">Interaction Notes</h4>
                <button class="btn" onclick="openAddCCNoteModal()">+ Add Note</button>
            </div>
            <div id="ccNotesList" class="notes-list">
                <!-- Notes will be rendered here -->
            </div>
        </div>
        <div id="ccLinkedSection" class="contact-panel-section" style="display: none;">
            <div class="linked-section">
                <div class="linked-header">
                    <h4 style="margin: 0;">🎯 Linked Opportunities</h4>
                    <button class="btn" onclick="openLinkOpportunityModal('company')">+ Link</button>
                </div>
                <div id="ccLinkedOpportunities" class="linked-list">
                    <!-- Will be rendered -->
                </div>
            </div>
            <div class="linked-section" style="margin-top: 20px;">
                <div class="linked-header">
                    <h4 style="margin: 0;">📋 Linked Proposals</h4>
                    <button class="btn" onclick="openLinkProposalModal('company')">+ Link</button>
                </div>
                <div id="ccLinkedProposals" class="linked-list">
                    <!-- Will be rendered -->
                </div>
            </div>
        </div>
    </div>

    <!-- Company Detail Panel (Slide-out) -->
    <div class="contact-panel-overlay" id="companyPanelOverlay" onclick="closeCompanyPanel()"></div>
    <div class="contact-panel" id="companyPanel" style="width: 500px;">
        <button class="contact-panel-close" onclick="closeCompanyPanel()">×</button>
        <div class="contact-panel-header">
            <h3 id="companyPanelName">Company Name</h3>
            <p id="companyPanelType">Company Type</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchCompanyPanelTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchCompanyPanelTab('contacts')">Contacts</button>
        </div>
        <div id="companyInfoSection" class="contact-panel-section">
            <!-- Company info will be rendered here -->
        </div>
        <div id="companyContactsSection" class="contact-panel-section" style="display: none;">
            <!-- Company contacts will be rendered here -->
        </div>
    </div>

    <!-- Company Contact Note Modal -->
    <div id="ccNoteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('ccNoteModal')">&times;</span>
            <h2 id="ccNoteModalTitle">Add Note</h2>
            <form id="ccNoteForm">
                <input type="hidden" id="ccNoteId">
                <input type="hidden" id="ccNoteContactId">
                <div class="form-group">
                    <label>Note Type</label>
                    <select id="ccNoteType">
                        <option value="General">General</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Call">Call</option>
                        <option value="Email">Email</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note *</label>
                    <textarea id="ccNoteText" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn">Save Note</button>
            </form>
        </div>
    </div>

    <!-- Link Opportunity Modal -->
    <div id="linkOpportunityModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('linkOpportunityModal')">&times;</span>
            <h2>Link Opportunity to Contact</h2>
            <input type="hidden" id="linkOppContactId">
            <input type="hidden" id="linkOppContactType">
            <div class="form-group">
                <label>Select Opportunity *</label>
                <select id="linkOppSelect" required>
                    <option value="">Choose an opportunity...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Role (optional)</label>
                <input type="text" id="linkOppRole" placeholder="e.g., Decision Maker, Technical Evaluator, Teaming Partner">
            </div>
            <button class="btn" onclick="saveLinkOpportunity()">Link Opportunity</button>
        </div>
    </div>

    <!-- Link Proposal Modal -->
    <div id="linkProposalModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('linkProposalModal')">&times;</span>
            <h2>Link Proposal to Contact</h2>
            <input type="hidden" id="linkPropContactId">
            <input type="hidden" id="linkPropContactType">
            <div class="form-group">
                <label>Select Proposal *</label>
                <select id="linkPropSelect" required>
                    <option value="">Choose a proposal...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Role (optional)</label>
                <input type="text" id="linkPropRole" placeholder="e.g., Decision Maker, Technical Evaluator, Teaming Partner">
            </div>
            <button class="btn" onclick="saveLinkProposal()">Link Proposal</button>
        </div>
    </div>

    <!-- Add Contact to Opportunity Modal -->
    <div id="addContactToOpportunityModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('addContactToOpportunityModal')">&times;</span>
            <h2>Add Contact to Opportunity</h2>
            <input type="hidden" id="addContactOppId">
            <input type="hidden" id="addContactOppContactId">
            <input type="hidden" id="addContactOppContactType">
            <div class="form-group">
                <label>Search Contact *</label>
                <div class="search-select-container">
                    <input type="text" id="addContactOppSearch" placeholder="Search by name..." oninput="filterContactsForOpportunity()" onfocus="showContactOppDropdown()">
                    <div class="search-select-dropdown" id="addContactOppDropdown"></div>
                </div>
                <div id="selectedContactOpp" class="selected-item-display" style="display: none;"></div>
            </div>
            <div class="form-group">
                <label>Role (optional)</label>
                <input type="text" id="addContactOppRole" placeholder="e.g., Decision Maker, Technical Evaluator, Teaming Partner">
            </div>
            <button class="btn" onclick="saveContactToOpportunity()">Add Contact</button>
        </div>
    </div>

    <!-- Add Contact to Proposal Modal -->
    <div id="addContactToProposalModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeModal('addContactToProposalModal')">&times;</span>
            <h2>Add Contact to Proposal</h2>
            <input type="hidden" id="addContactPropId">
            <input type="hidden" id="addContactPropContactId">
            <input type="hidden" id="addContactPropContactType">
            <div class="form-group">
                <label>Search Contact *</label>
                <div class="search-select-container">
                    <input type="text" id="addContactPropSearch" placeholder="Search by name..." oninput="filterContactsForProposal()" onfocus="showContactPropDropdown()">
                    <div class="search-select-dropdown" id="addContactPropDropdown"></div>
                </div>
                <div id="selectedContactProp" class="selected-item-display" style="display: none;"></div>
            </div>
            <div class="form-group">
                <label>Role (optional)</label>
                <input type="text" id="addContactPropRole" placeholder="e.g., Decision Maker, Technical Evaluator, Teaming Partner">
            </div>
            <button class="btn" onclick="saveContactToProposal()">Add Contact</button>
        </div>
    </div>

    <!-- Task Detail Panel (Slide-out) -->
    <div class="task-panel-overlay" id="taskPanelOverlay" onclick="closeTaskPanel()"></div>
    <div class="task-detail-panel" id="taskDetailPanel">
        <button class="task-panel-close" onclick="closeTaskPanel()">×</button>
        <div class="task-panel-header">
            <h3 id="taskPanelTitle">Task Details</h3>
            <p id="taskPanelDate" style="margin: 5px 0 0; opacity: 0.9;"></p>
        </div>
        <div class="task-panel-body" id="taskPanelBody">
            <!-- Task details will be rendered here -->
        </div>
        <div class="task-panel-actions">
            <button class="btn" onclick="editTaskFromPanel()" style="flex: 2;">✏️ Edit Task</button>
            <button class="btn btn-secondary" onclick="closeTaskPanel()">Close</button>
        </div>
    </div>

    <!-- Contact Notes Panel -->
    <div class="contact-panel-overlay" id="contactPanelOverlay" onclick="closeContactPanel()"></div>
    <div class="contact-panel" id="contactPanel">
        <button class="contact-panel-close" onclick="closeContactPanel()">×</button>
        <div class="contact-panel-header">
            <h3 id="contactPanelName">Contact Name</h3>
            <p id="contactPanelTitle">Title - Agency</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchContactTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchContactTab('notes')">Notes</button>
            <button class="contact-panel-tab" onclick="switchContactTab('linked')">Linked</button>
        </div>
        <div id="contactInfoSection" class="contact-panel-section">
            <!-- Contact info will be rendered here -->
        </div>
        <div id="contactNotesSection" class="contact-panel-section" style="display: none;">
            <div class="notes-header">
                <h4 style="margin: 0;">Interaction Notes</h4>
                <button class="btn" onclick="openAddNoteModal()">+ Add Note</button>
            </div>
            <div class="notes-filters">
                <div class="notes-filter-group">
                    <label>From Date</label>
                    <input type="date" id="noteFilterDateFrom" onchange="filterContactNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>To Date</label>
                    <input type="date" id="noteFilterDateTo" onchange="filterContactNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>User</label>
                    <select id="noteFilterUser" onchange="filterContactNotes()">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="notes-filter-group">
                    <label>Interaction Type</label>
                    <input type="text" id="noteFilterType" placeholder="Filter..." oninput="filterContactNotes()">
                </div>
            </div>
            <div id="contactNotesList">
                <!-- Notes will be rendered here -->
            </div>
        </div>
        <div id="contactLinkedSection" class="contact-panel-section" style="display: none;">
            <div class="linked-section">
                <div class="linked-header">
                    <h4 style="margin: 0;">🎯 Linked Opportunities</h4>
                    <button class="btn" onclick="openLinkOpportunityModal('federal')">+ Link</button>
                </div>
                <div id="contactLinkedOpportunities" class="linked-list">
                    <!-- Will be rendered -->
                </div>
            </div>
            <div class="linked-section" style="margin-top: 20px;">
                <div class="linked-header">
                    <h4 style="margin: 0;">📋 Linked Proposals</h4>
                    <button class="btn" onclick="openLinkProposalModal('federal')">+ Link</button>
                </div>
                <div id="contactLinkedProposals" class="linked-list">
                    <!-- Will be rendered -->
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeNoteModal()">&times;</span>
            <h2 id="noteModalTitle">Add Note</h2>
            <form id="noteForm" onsubmit="saveContactNote(event)">
                <input type="hidden" id="noteId">
                <input type="hidden" id="noteContactId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Date</label>
                    <input type="date" id="noteDate" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Interaction Type</label>
                    <input type="text" id="noteInteractionType" placeholder="e.g., Phone Call, Email, Meeting..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Note *</label>
                    <textarea id="noteText" rows="5" required placeholder="Enter your note here..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn">Save Note</button>
            </form>
        </div>
    </div>

    <!-- Opportunity Panel -->
    <div class="opp-panel-overlay" id="oppPanelOverlay" onclick="closeOpportunityPanel()"></div>
    <div class="opp-panel" id="oppPanel">
        <button class="contact-panel-close" onclick="closeOpportunityPanel()">×</button>
        <div class="contact-panel-header">
            <h3 id="oppPanelTitle">Opportunity Title</h3>
            <p id="oppPanelSubtitle">Agency - Status</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchOppTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchOppTab('notes')">Notes</button>
        </div>
        <div id="oppInfoSection" class="contact-panel-section">
            <!-- Opportunity info will be rendered here -->
        </div>
        <div id="oppNotesSection" class="contact-panel-section" style="display: none;">
            <div class="notes-header">
                <h4 style="margin: 0;">Interaction Notes</h4>
                <button class="btn" onclick="openAddOppNoteModal()">+ Add Note</button>
            </div>
            <div class="notes-filters">
                <div class="notes-filter-group">
                    <label>From Date</label>
                    <input type="date" id="oppNoteFilterDateFrom" onchange="filterOpportunityNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>To Date</label>
                    <input type="date" id="oppNoteFilterDateTo" onchange="filterOpportunityNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>User</label>
                    <select id="oppNoteFilterUser" onchange="filterOpportunityNotes()">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="notes-filter-group">
                    <label>Interaction Type</label>
                    <input type="text" id="oppNoteFilterType" placeholder="Filter..." oninput="filterOpportunityNotes()">
                </div>
            </div>
            <div id="oppNotesList">
                <!-- Notes will be rendered here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit Opportunity Note Modal -->
    <div id="oppNoteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeOppNoteModal()">&times;</span>
            <h2 id="oppNoteModalTitle">Add Note</h2>
            <form id="oppNoteForm" onsubmit="saveOpportunityNote(event)">
                <input type="hidden" id="oppNoteId">
                <input type="hidden" id="oppNoteOpportunityId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Date</label>
                    <input type="date" id="oppNoteDate" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Interaction Type</label>
                    <input type="text" id="oppNoteInteractionType" placeholder="e.g., Phone Call, Email, Meeting..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Note *</label>
                    <textarea id="oppNoteText" rows="5" required placeholder="Enter your note here..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn">Save Note</button>
            </form>
        </div>
    </div>

    <!-- Proposal Panel (Slide-out) -->
    <div class="opp-panel-overlay" id="propPanelOverlay" onclick="closeProposalPanel()"></div>
    <div class="opp-panel" id="propPanel">
        <button class="contact-panel-close" onclick="closeProposalPanel()">×</button>
        <div class="contact-panel-header" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <h3 id="propPanelTitle">Proposal Title</h3>
            <p id="propPanelSubtitle">Agency - Status</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchPropTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchPropTab('notes')">Notes</button>
        </div>
        <div id="propInfoSection" class="contact-panel-section">
            <!-- Proposal info will be rendered here -->
        </div>
        <div id="propNotesSection" class="contact-panel-section" style="display: none;">
            <div class="notes-header">
                <h4 style="margin: 0;">Interaction Notes</h4>
                <button class="btn" onclick="openAddPropNoteModal()">+ Add Note</button>
            </div>
            <div class="notes-filters">
                <div class="notes-filter-group">
                    <label>From Date</label>
                    <input type="date" id="propNoteFilterDateFrom" onchange="filterProposalNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>To Date</label>
                    <input type="date" id="propNoteFilterDateTo" onchange="filterProposalNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>User</label>
                    <select id="propNoteFilterUser" onchange="filterProposalNotes()">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="notes-filter-group">
                    <label>Interaction Type</label>
                    <input type="text" id="propNoteFilterType" placeholder="Filter..." oninput="filterProposalNotes()">
                </div>
            </div>
            <div id="propNotesList">
                <!-- Notes will be rendered here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit Proposal Note Modal -->
    <div id="propNoteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closePropNoteModal()">&times;</span>
            <h2 id="propNoteModalTitle">Add Note</h2>
            <form id="propNoteForm" onsubmit="saveProposalNote(event)">
                <input type="hidden" id="propNoteId">
                <input type="hidden" id="propNoteProposalId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Date</label>
                    <input type="date" id="propNoteDate" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Interaction Type</label>
                    <input type="text" id="propNoteInteractionType" placeholder="e.g., Phone Call, Email, Meeting..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Note *</label>
                    <textarea id="propNoteText" rows="5" required placeholder="Enter your note here..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn">Save Note</button>
            </form>
        </div>
    </div>

    <!-- Assign Proposal Users Modal -->
    <div id="assignPropUsersModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeAssignPropUsersModal()">&times;</span>
            <h2>Manage Assigned Users</h2>
            <p style="color: #6c757d; margin-bottom: 15px;">Select team members to assign to this proposal.</p>
            <div id="propUserCheckboxList" class="checkbox-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #e1e5e9; border-radius: 8px; padding: 10px;">
                <!-- User checkboxes will be rendered here -->
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button class="btn" onclick="savePropUserAssignments()">Save Assignments</button>
                <button class="btn btn-secondary" onclick="closeAssignPropUsersModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Assign Proposal Contacts Modal -->
    <div id="assignPropContactsModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeAssignPropContactsModal()">&times;</span>
            <h2>Manage Assigned Contacts</h2>
            <p style="color: #6c757d; margin-bottom: 15px;">Select stakeholder contacts to associate with this proposal.</p>
            <div id="propContactCheckboxList" class="checkbox-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #e1e5e9; border-radius: 8px; padding: 10px;">
                <!-- Contact checkboxes will be rendered here -->
            </div>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button class="btn" onclick="savePropContactAssignments()">Save Assignments</button>
                <button class="btn btn-secondary" onclick="closeAssignPropContactsModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Event Panel (Slide-out) -->
    <div class="opp-panel-overlay" id="eventPanelOverlay" onclick="closeEventPanel()"></div>
    <div class="opp-panel" id="eventPanel">
        <button class="contact-panel-close" onclick="closeEventPanel()">×</button>
        <div class="contact-panel-header">
            <h3 id="eventPanelTitle">Event Title</h3>
            <p id="eventPanelSubtitle">Type - Status</p>
        </div>
        <div class="contact-panel-tabs">
            <button class="contact-panel-tab active" onclick="switchEventTab('info')">Info</button>
            <button class="contact-panel-tab" onclick="switchEventTab('notes')">Notes</button>
        </div>
        <div id="eventInfoSection" class="contact-panel-section">
            <!-- Event info will be rendered here -->
        </div>
        <div id="eventNotesSection" class="contact-panel-section" style="display: none;">
            <div class="notes-header">
                <h4 style="margin: 0;">Interaction Notes</h4>
                <button class="btn" onclick="openAddEventNoteModal()">+ Add Note</button>
            </div>
            <div class="notes-filters">
                <div class="notes-filter-group">
                    <label>From Date</label>
                    <input type="date" id="eventNoteFilterDateFrom" onchange="filterEventNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>To Date</label>
                    <input type="date" id="eventNoteFilterDateTo" onchange="filterEventNotes()">
                </div>
                <div class="notes-filter-group">
                    <label>User</label>
                    <select id="eventNoteFilterUser" onchange="filterEventNotes()">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="notes-filter-group">
                    <label>Interaction Type</label>
                    <input type="text" id="eventNoteFilterType" placeholder="Filter..." oninput="filterEventNotes()">
                </div>
            </div>
            <div id="eventNotesList">
                <!-- Notes will be rendered here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit Event Note Modal -->
    <div id="eventNoteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <span class="close" onclick="closeEventNoteModal()">&times;</span>
            <h2 id="eventNoteModalTitle">Add Note</h2>
            <form id="eventNoteForm" onsubmit="saveEventNote(event)">
                <input type="hidden" id="eventNoteId">
                <input type="hidden" id="eventNoteEventId">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Date</label>
                    <input type="date" id="eventNoteDate" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Interaction Type</label>
                    <input type="text" id="eventNoteInteractionType" placeholder="e.g., Phone Call, Email, Meeting..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Note *</label>
                    <textarea id="eventNoteText" rows="5" required placeholder="Enter your note here..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; resize: vertical;"></textarea>
                </div>
                <button type="submit" class="btn">Save Note</button>
            </form>
        </div>
    </div>

    <!-- Department Event Modal -->
    <div id="deptEventModal" class="modal">
        <div class="modal-content" style="max-width: 550px;">
            <span class="close" onclick="closeDeptEventModal()">&times;</span>
            <h2 id="deptEventModalTitle">➕ Add Department Event</h2>
            <form id="deptEventForm" onsubmit="saveDeptEvent(event)">
                <input type="hidden" id="deptEventId">
                <input type="hidden" id="deptEventParentId">
                <input type="hidden" id="deptEventOriginalDate">
                
                <!-- Title -->
                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" id="deptEventTitle" required placeholder="Enter event title">
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="deptEventDescription" rows="3" placeholder="Enter event description (optional)"></textarea>
                </div>
                
                <!-- Date Fields -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" id="deptEventStartDate" required>
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" id="deptEventEndDate" required>
                    </div>
                </div>
                
                <!-- Color Selection -->
                <div class="form-group">
                    <label>Event Color</label>
                    <div class="color-palette" id="deptEventColorPalette">
                        <!-- Colors will be rendered by JS -->
                    </div>
                    <input type="hidden" id="deptEventColor" value="#667eea">
                </div>
                
                <!-- Recurring Event -->
                <div class="form-group" style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                        <input type="checkbox" id="deptEventRecurring" onchange="toggleDeptRecurrenceOptions()" style="width: 18px; height: 18px; accent-color: #667eea;">
                        <span>🔄 Recurring Event</span>
                    </label>
                    
                    <div id="deptRecurrenceOptions" class="recurrence-options" style="display: none;">
                        <!-- Recurrence Type -->
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size: 0.9rem;">Repeat</label>
                            <select id="deptEventRecurrenceType" class="form-control">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="biweekly">Every 2 Weeks</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        
                        <!-- Recurrence End -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 0.9rem;">Ends</label>
                            <div class="recurrence-end-option">
                                <input type="radio" name="deptRecurrenceEnd" value="date" id="deptRecurrenceEndDate" checked onchange="toggleDeptRecurrenceEndFields()">
                                <label for="deptRecurrenceEndDate">On date:</label>
                                <input type="date" id="deptEventRecurrenceEndDate">
                            </div>
                            <div class="recurrence-end-option">
                                <input type="radio" name="deptRecurrenceEnd" value="count" id="deptRecurrenceEndCount" onchange="toggleDeptRecurrenceEndFields()">
                                <label for="deptRecurrenceEndCount">After</label>
                                <input type="number" id="deptEventRecurrenceCount" value="10" min="1" max="365" style="width: 70px;" disabled>
                                <span>occurrences</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Created By (read-only) -->
                <div id="deptEventCreatedByDisplay" style="padding: 10px 15px; background: #f0f0f0; border-radius: 8px; color: #6c757d; font-size: 0.9rem; margin-bottom: 20px;">
                    <strong>Created by:</strong> <span id="deptEventCreatedBy"><?php echo htmlspecialchars($_SESSION["display_name"] ?? $_SESSION["username"]); ?></span>
                </div>
                
                <!-- Buttons -->
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger" id="deptEventDeleteBtn" style="display: none; margin-right: auto;" onclick="confirmDeleteDeptEvent()">🗑️ Delete</button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeptEventModal()">Cancel</button>
                    <button type="submit" class="btn">💾 Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recurring Event Edit Options Modal -->
    <div id="recurringEditModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="closeRecurringEditModal()">&times;</span>
            <h3 style="margin: 0 0 15px 0;">🔄 Edit Recurring Event</h3>
            <p style="color: #6c757d; margin-bottom: 20px;">This is a recurring event. What would you like to edit?</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn" style="text-align: left; background: white; border: 2px solid #667eea; color: #667eea;" onclick="editRecurringOption('single')">
                    📌 Only this instance
                    <div style="font-weight: normal; font-size: 0.85rem; color: #6c757d;">Edit just this one occurrence</div>
                </button>
                <button class="btn" style="text-align: left; background: white; border: 2px solid #667eea; color: #667eea;" onclick="editRecurringOption('future')">
                    ➡️ This and all future instances
                    <div style="font-weight: normal; font-size: 0.85rem; color: #6c757d;">Edit this and upcoming occurrences</div>
                </button>
                <button class="btn" style="text-align: left; background: white; border: 2px solid #667eea; color: #667eea;" onclick="editRecurringOption('all')">
                    🔄 All instances
                    <div style="font-weight: normal; font-size: 0.85rem; color: #6c757d;">Edit the entire recurring series</div>
                </button>
            </div>
            <button class="btn btn-secondary" style="margin-top: 15px; width: 100%;" onclick="closeRecurringEditModal()">Cancel</button>
        </div>
    </div>

    <!-- Recurring Event Delete Options Modal -->
    <div id="recurringDeleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" onclick="closeRecurringDeleteModal()">&times;</span>
            <h3 style="margin: 0 0 15px 0;">🗑️ Delete Recurring Event</h3>
            <p style="color: #6c757d; margin-bottom: 20px;">This is a recurring event. What would you like to delete?</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="btn btn-danger" style="text-align: left;" onclick="deleteRecurringOption('single')">
                    📌 Only this instance
                    <div style="font-weight: normal; font-size: 0.85rem;">Delete just this one occurrence</div>
                </button>
                <button class="btn btn-danger" style="text-align: left;" onclick="deleteRecurringOption('all')">
                    🔄 All instances
                    <div style="font-weight: normal; font-size: 0.85rem;">Delete the entire recurring series</div>
                </button>
            </div>
            <button class="btn btn-secondary" style="margin-top: 15px; width: 100%;" onclick="closeRecurringDeleteModal()">Cancel</button>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p>Loading CRM data...</p>
    </div>
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
    // Toast notification system
    function showToast(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Debounce utility
    function debounce(fn, delay = 300) {
        let timer;
        return function(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Debounced search handlers for main table filters
    const debouncedFilterAgencies = debounce(() => filterAgenciesView(), 300);
    const debouncedFilterCompanies = debounce(() => filterCompanies(), 300);
    const debouncedFilterCompanyContacts = debounce(() => filterCompanyContacts(), 300);
    const debouncedFilterEvents = debounce(() => filterEventsTable(), 300);

    // Global state
    let agencies = [], contacts = [], opportunities = [], proposals = [], tasks = [], events = [];
    let companies = [], companyContacts = [], companySmallBusinessStatuses = {}, companyVehicles = {}, companyContactAgencies = {}, companyCoreCustomers = {};
    let contactOpportunities = {}, contactProposals = {}, companyContactOpportunities = {}, companyContactProposals = {};
    let opportunityContacts = {}, proposalContacts = {};
    let eventUsers = {}, eventFederalContacts = {}, eventCommercialContacts = {};
    let users = [], userPermissions = {};
    let currentUserId = null, currentUsername = '', currentDisplayName = '', currentRole = '';
    let currentEditId = null;
    const API_URL = 'api.php';
    
    // Calendar state
    let calendarView = 'month'; // 'month', 'week', 'day'
    let calendarDate = new Date(); // Current viewing date
    let selectedDate = null; // Selected date for day view
    let calendarTaskId = null; // Currently viewed task in panel
    
    // Contact Notes state
    let currentContactId = null;
    let currentContactNotes = [];
    let editingNoteId = null;
    
    // Opportunity state
    let currentOpportunityId = null;
    let currentOpportunityData = null;
    let currentOpportunityNotes = [];
    
    // Event state
    let currentEventId = null;
    let currentEventData = null;
    let currentEventNotes = [];
    
    // Table sorting state - default to Due Date ascending
    let sortState = {
        opportunities: { field: 'dueDate', direction: 'asc' },
        proposals: { field: 'dueDate', direction: 'asc' },
        tasks: { field: 'dueDate', direction: 'asc' }
    };
    let editingOppNoteId = null;
    
    // Proposal state
    let currentProposalId = null;
    let currentProposalData = null;
    let currentProposalNotes = [];
    let editingPropNoteId = null;
    
    // Divisions state
    let divisions = [];
    
    // Company Contact state
    let currentCompanyContactId = null;
    let currentCompanyContactData = null;
    let currentCompanyPanelId = null;
    let currentCompanyPanelData = null;
    let currentCompanyContactNotes = [];
    let editingCCNoteId = null;
    let ccSelectedAgencyIds = [];
    let ccSelectedSecondaryOwnerIds = [];
    let companyContactSecondaryOwners = {};
    let companySelectedCoreCustomerIds = [];
    let companyCustomVehicles = [];

    // Initialize
    document.addEventListener('DOMContentLoaded', fetchAllData);

    // Fetch all data from API
    async function fetchAllData() {
        const overlay = document.getElementById('loadingOverlay');
        try {
            const response = await fetch(`${API_URL}?action=getAllData`);
            if (!response.ok) throw new Error(`API request failed: ${response.status}`);

            const data = await response.json();
            
            userPermissions = data.permissions || {};
            agencies = data.agencies || [];
            contacts = data.contacts || [];
            opportunities = data.opportunities || [];
            proposals = data.proposals || [];
            tasks = data.tasks || [];
            events = data.events || [];
            eventUsers = data.eventUsers || {};
            eventFederalContacts = data.eventFederalContacts || {};
            eventCommercialContacts = data.eventCommercialContacts || {};
            users = data.users || [];
            divisions = data.divisions || [];
            
            // Companies and Company Contacts
            companies = data.companies || [];
            companyContacts = data.companyContacts || [];
            companySmallBusinessStatuses = data.companySmallBusinessStatuses || {};
            companyVehicles = data.companyVehicles || {};
            companyContactAgencies = data.companyContactAgencies || {};
            companyContactSecondaryOwners = data.companyContactSecondaryOwners || {};
            companyCoreCustomers = data.companyCoreCustomers || {};
            
            // Contact-Opportunity-Proposal associations
            contactOpportunities = data.contactOpportunities || {};
            contactProposals = data.contactProposals || {};
            companyContactOpportunities = data.companyContactOpportunities || {};
            companyContactProposals = data.companyContactProposals || {};
            opportunityContacts = data.opportunityContacts || {};
            proposalContacts = data.proposalContacts || {};
            
            currentUserId = data.currentUserId;
            currentUsername = data.currentUsername;
            currentDisplayName = data.currentDisplayName || data.currentUsername;
            currentRole = data.currentRole;
            
            // Check if user has specialty role (view-only access to their records)
            window.isSpecialty = currentRole === 'specialty';

            applyPermissionsToUI();
            populateSelects();
            updateDashboard();
            populateTables();
            populateEventsTable();
            populateCompaniesTable();
            populateCompanyContactsTable();
            populateCompanyFilters();
            setupSearch();
            setupCharts();
            loadMyTasks();
            initCalendar();
            populateKanbanUserFilter();
            renderKanbanBoard();

            if (overlay) overlay.style.display = 'none';
        } catch (error) {
            console.error('Error fetching data:', error);
            if (overlay) overlay.style.display = 'none';
            showToast('Could not load application data. Please check the console for details.', 'error', 5000);
        }
    }

    // Apply permissions to UI elements
    function applyPermissionsToUI() {
        document.querySelectorAll('.nav-tab[data-tab-for]').forEach(tab => {
            const resource = tab.getAttribute('data-tab-for');
            // Skip permission check for these tabs - always visible to all users (except specialty)
            const alwaysVisibleTabs = ['dashboard', 'calendar', 'kanban', 'deptcalendar'];
            
            // Hide Admin tab for non-admin users
            if (resource === 'admin' && currentRole !== 'admin') {
                tab.setAttribute('data-hidden', 'true');
                return;
            }
            
            // Hide Jira tab for specialty users
            if (resource === 'jira' && window.isSpecialty) {
                tab.setAttribute('data-hidden', 'true');
                return;
            }
            
            // Hide Calendar, Dept Calendar, and Organizations & Contacts tabs for specialty users
            if ((resource === 'calendar' || resource === 'deptcalendar' || resource === 'contact') && window.isSpecialty) {
                tab.setAttribute('data-hidden', 'true');
                return;
            }
            
            if (alwaysVisibleTabs.includes(resource)) {
                tab.removeAttribute('data-hidden');
            } else if (resource === 'mytasks' && !userPermissions.mytasks?.can_view) {
                tab.setAttribute('data-hidden', 'true');
            } else if (!alwaysVisibleTabs.includes(resource) && !userPermissions[resource]?.can_view) {
                tab.setAttribute('data-hidden', 'true');
            } else {
                tab.removeAttribute('data-hidden');
            }
        });
        
        document.querySelectorAll('button[data-permission]').forEach(button => {
            const [resource, action] = button.getAttribute('data-permission').split('.');
            
            // Specialty users can't create anything
            if (window.isSpecialty && action === 'create') {
                button.setAttribute('data-hidden', 'true');
                return;
            }
            
            if (!userPermissions[resource]?.[`can_${action}`]) {
                button.setAttribute('data-hidden', 'true');
            } else {
                button.removeAttribute('data-hidden');
            }
        });
    }

    // Populate select dropdowns
    function populateSelects() {
        const agencyOptions = '<option value="">Select Agency</option>' + agencies.map(a => `<option value="${a.id}">${escapeHtml(a.name)}</option>`).join('');
        const userOptions = '<option value="">Select User</option>' + users.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || u.username)}</option>`).join('');
        
        ['contactAgency', 'opportunityAgency', 'proposalAgency'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = agencyOptions;
        });
        
        ['contactOwner', 'opportunityOwner', 'proposalOwner', 'taskAssignedTo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = userOptions;
        });

        // Agency filter for contacts
        const agencyFilter = document.getElementById('agencyFilter');
        if (agencyFilter) {
            agencyFilter.innerHTML = '<option value="ALL">All Agencies</option>' + agencies.map(a => `<option value="${a.id}">${a.name}</option>`).join('');
        }
    }

    // Update dashboard counts
    function updateDashboard() {
        document.getElementById('totalAgencies').textContent = agencies.length;
        document.getElementById('totalContacts').textContent = contacts.filter(c => c.status === 'Active').length;
        document.getElementById('totalOpportunities').textContent = opportunities.filter(o => o.status === 'Lead' || o.status === 'Qualified').length;
        document.getElementById('totalProposals').textContent = proposals.filter(p => ['Draft', 'Submitted', 'Under Review'].includes(p.status)).length;
    }

    // Get actions HTML based on permissions
    function getActionsHtml(type, id, noWrapper = false) {
        let html = noWrapper ? '' : '<div class="action-buttons">';
        
        // Specialty users cannot edit or delete
        if (!window.isSpecialty) {
            if (userPermissions[type]?.can_update) html += `<button class="action-btn edit" onclick="editItem('${type}', ${id})">Edit</button>`;
            if (userPermissions[type]?.can_delete) html += `<button class="action-btn delete" onclick="deleteItem('${type}', ${id})">Delete</button>`;
        }
        
        return noWrapper ? html : html + '</div>';
    }

    // =============================================
    // TABLE SORTING FUNCTIONS
    // =============================================
    
    function sortTable(tableType, field) {
        // Toggle direction if same field, otherwise default to ascending
        if (sortState[tableType].field === field) {
            sortState[tableType].direction = sortState[tableType].direction === 'asc' ? 'desc' : 'asc';
        } else {
            sortState[tableType].field = field;
            sortState[tableType].direction = 'asc';
        }
        
        // Update header UI
        updateSortHeaders(tableType);
        
        // Re-populate the table
        if (tableType === 'opportunities') populateOpportunitiesTable();
        else if (tableType === 'proposals') populateProposalsTable();
        else if (tableType === 'tasks') populateTasksTable();
    }
    
    function updateSortHeaders(tableType) {
        const tableId = tableType === 'opportunities' ? 'opportunitiesTable' 
                      : tableType === 'proposals' ? 'proposalsTable' 
                      : 'tasksTable';
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const headers = table.querySelectorAll('th.sortable');
        headers.forEach(th => {
            th.classList.remove('active-sort', 'asc', 'desc');
            const arrow = th.querySelector('.sort-arrow');
            if (arrow) arrow.textContent = '';
            
            if (th.dataset.sort === sortState[tableType].field) {
                th.classList.add('active-sort', sortState[tableType].direction);
                if (arrow) arrow.textContent = sortState[tableType].direction === 'asc' ? '▲' : '▼';
            }
        });
    }
    
    function getSortedData(data, tableType) {
        const { field, direction } = sortState[tableType];
        
        return [...data].sort((a, b) => {
            let valA = a[field];
            let valB = b[field];
            
            // Handle owner/assigned display names
            if (field === 'ownerDisplayName') {
                valA = a.ownerDisplayName || a.ownerUsername || '';
                valB = b.ownerDisplayName || b.ownerUsername || '';
            }
            if (field === 'assignedToDisplayName') {
                valA = a.assignedToDisplayName || a.assignedToUsername || a.assignedTo || '';
                valB = b.assignedToDisplayName || b.assignedToUsername || b.assignedTo || '';
            }
            
            // Handle null/undefined values - push them to the end
            if (valA == null || valA === '') valA = direction === 'asc' ? 'zzzzz' : '';
            if (valB == null || valB === '') valB = direction === 'asc' ? 'zzzzz' : '';
            
            // Handle numeric fields
            if (field === 'value' || field === 'winProbability') {
                valA = parseFloat(valA) || 0;
                valB = parseFloat(valB) || 0;
                return direction === 'asc' ? valA - valB : valB - valA;
            }
            
            // Handle priority field (custom sort order)
            if (field === 'priority') {
                const priorityOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
                valA = priorityOrder[valA] || 4;
                valB = priorityOrder[valB] || 4;
                return direction === 'asc' ? valA - valB : valB - valA;
            }
            
            // Handle date fields
            if (field === 'dueDate' || field === 'submitDate' || field === 'validity_date' || field === 'award_date') {
                // Treat null/empty dates as far future for ascending, far past for descending
                const dateA = valA && valA !== 'zzzzz' ? new Date(valA) : (direction === 'asc' ? new Date('9999-12-31') : new Date('1900-01-01'));
                const dateB = valB && valB !== 'zzzzz' ? new Date(valB) : (direction === 'asc' ? new Date('9999-12-31') : new Date('1900-01-01'));
                return direction === 'asc' ? dateA - dateB : dateB - dateA;
            }
            
            // Default string comparison
            valA = String(valA).toLowerCase();
            valB = String(valB).toLowerCase();
            
            if (valA < valB) return direction === 'asc' ? -1 : 1;
            if (valA > valB) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    }

    // Populate all tables
    function populateTables() {
        populateAgenciesTable();
        populateContactsTable();
        populateOpportunitiesTable();
        populateProposalsTable();
        populateTasksTable();
    }

    function populateAgenciesTable() {
        const container = document.getElementById('agenciesHierarchyView');
        if (!container) return;
        
        const canCreateDivision = userPermissions.division?.can_create === true;
        const canDeleteDivision = userPermissions.division?.can_delete === true;
        
        // Build a map of divisions from contacts (text field) grouped by agency_id
        const contactDivisionsMap = {};
        contacts.forEach(c => {
            if (c.division && c.agency_id) {
                if (!contactDivisionsMap[c.agency_id]) {
                    contactDivisionsMap[c.agency_id] = new Set();
                }
                contactDivisionsMap[c.agency_id].add(c.division.trim());
            }
        });
        
        let html = '';
        agencies.forEach(agency => {
            // Get divisions from divisions table
            const tableDivisions = divisions.filter(d => d.agency_id == agency.id);
            const tableDivisionNames = new Set(tableDivisions.map(d => d.name));
            
            // Get divisions from contacts for this agency
            const contactDivisionNames = contactDivisionsMap[agency.id] || new Set();
            
            // Combine: table divisions (with IDs) + contact divisions not in table (no IDs)
            const allDivisions = [...tableDivisions];
            contactDivisionNames.forEach(name => {
                if (!tableDivisionNames.has(name)) {
                    allDivisions.push({ id: null, name: name, fromContacts: true });
                }
            });
            
            // Sort divisions alphabetically
            allDivisions.sort((a, b) => a.name.localeCompare(b.name));
            
            html += `
                <div class="agency-card" data-agency-id="${agency.id}">
                    <div class="agency-card-header">
                        <div class="agency-card-title">
                            <span>${agency.name || ''}</span>
                        </div>
                        <div class="agency-card-meta">
                            <span>${agency.location || 'N/A'}</span>
                            <span>${agency.type || 'N/A'}</span>
                            <span class="status-badge status-${(agency.status || '').toLowerCase()}">${agency.status || ''}</span>
                            <span>${agency.contactCount || 0} ${Number(agency.contactCount) === 1 ? 'contact' : 'contacts'}</span>
                        </div>
                        <div class="agency-card-actions">
                            ${getActionsHtml('agency', agency.id)}
                        </div>
                    </div>
                    <div class="division-list">
                        ${allDivisions.map(div => `
                            <div class="division-row" data-division-id="${div.id || ''}" ${div.fromContacts ? 'data-from-contacts="true"' : ''}>
                                <div class="division-name">
                                    <span class="division-arrow">↳</span>
                                    <span>${div.name}</span>
                                    ${div.fromContacts ? '<span class="division-source-badge">(from contacts)</span>' : ''}
                                </div>
                                ${canDeleteDivision && div.id ? `
                                    <div class="division-actions">
                                        <button class="btn-delete-division" onclick="deleteDivision(${div.id}, '${div.name.replace(/'/g, "\\'")}')">Delete</button>
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                        ${canCreateDivision ? `
                            <div class="add-division-row">
                                <span class="division-arrow" style="color: #28a745;">+</span>
                                <input type="text" id="newDivision_${agency.id}" placeholder="Enter division name..." onkeypress="if(event.key==='Enter') addDivision(${agency.id})">
                                <button class="btn-add-division" onclick="addDivision(${agency.id})">Add Division</button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<div class="empty-state-msg"><p>No agencies found</p><small>Add an agency using the button above, or adjust your search.</small></div>';
    }
    
    function filterAgenciesView() {
        const searchTerm = document.getElementById('agencySearch')?.value.toLowerCase() || '';
        const cards = document.querySelectorAll('.agency-card');
        
        cards.forEach(card => {
            const agencyName = card.querySelector('.agency-card-title span')?.textContent.toLowerCase() || '';
            const divisionRows = card.querySelectorAll('.division-row');
            let divisionMatch = false;
            
            divisionRows.forEach(row => {
                // Get the division name (second span in division-name, not the arrow or badge)
                const spans = row.querySelectorAll('.division-name > span');
                const divName = spans.length > 1 ? spans[1].textContent.toLowerCase() : '';
                if (divName.includes(searchTerm)) {
                    divisionMatch = true;
                }
            });
            
            if (agencyName.includes(searchTerm) || divisionMatch || searchTerm === '') {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    async function addDivision(agencyId) {
        const input = document.getElementById(`newDivision_${agencyId}`);
        const name = input?.value.trim();
        
        if (!name) {
            showToast('Please enter a division name.', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=saveDivision`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ agency_id: agencyId, name: name })
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Failed to add division');
            }
            
            // Clear input and refresh data
            input.value = '';
            await fetchAllData();
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        }
    }
    
    async function deleteDivision(divisionId, divisionName) {
        if (!confirm(`Are you sure you want to delete the division "${divisionName}"?`)) {
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=deleteDivision&id=${divisionId}`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.error || 'Failed to delete division');
            }
            
            await fetchAllData();
        } catch (error) {
            showToast('Error: ' + error.message, 'error');
        }
    }

    function populateContactsTable() {
        const container = document.getElementById('contactsHierarchyView');
        if (!container) return;
        
        const agencyFilterVal = document.getElementById('agencyFilter')?.value || 'ALL';
        let filtered = contacts.filter(c => c.status === 'Active');
        if (agencyFilterVal !== 'ALL') {
            filtered = filtered.filter(c => c.agency_id == agencyFilterVal);
        }
        filtered.sort((a, b) => (a.agencyName || '').localeCompare(b.agencyName || '') || (a.lastName || '').localeCompare(b.lastName || ''));
        
        // Group contacts by agency
        const agencyGroups = {};
        filtered.forEach(contact => {
            const agencyName = contact.agencyName || 'No Agency';
            const agencyId = contact.agency_id || 0;
            if (!agencyGroups[agencyId]) {
                agencyGroups[agencyId] = {
                    name: agencyName,
                    contacts: []
                };
            }
            agencyGroups[agencyId].contacts.push(contact);
        });
        
        let html = '';
        
        // Sort agencies by name
        const sortedAgencyIds = Object.keys(agencyGroups).sort((a, b) => 
            agencyGroups[a].name.localeCompare(agencyGroups[b].name)
        );
        
        sortedAgencyIds.forEach(agencyId => {
            const group = agencyGroups[agencyId];

            html += `
                <div class="contact-agency-card" data-agency-id="${agencyId}">
                    <div class="contact-agency-header">
                        <div class="contact-agency-title">${group.name}</div>
                        <div class="contact-agency-meta">
                            <span>${group.contacts.length} contacts</span>
                        </div>
                    </div>
                    <div class="contact-list">
                        <div class="contact-row contact-header-row">
                            <div class="contact-name-cell">Contact</div>
                            <div class="contact-division">Division</div>
                            <div class="contact-role">Role</div>
                            <div class="contact-owner">Owner</div>
                            <div class="contact-actions">Actions</div>
                        </div>
                        ${group.contacts.map(item => {
                            const ownerName = item.ownerDisplayName || item.ownerUsername || users.find(u => u.id == item.owner_user_id)?.display_name || users.find(u => u.id == item.owner_user_id)?.username || '—';

                            return `
                                <div class="contact-row" data-contact-id="${item.id}">
                                    <div class="contact-name-cell">
                                        <span class="contact-name" onclick="showContactDetail(${item.id})">${item.firstName || ''} ${item.lastName || ''}</span>
                                        <span class="contact-email">${item.email || ''}</span>
                                    </div>
                                    <div class="contact-division">${item.division || '—'}</div>
                                    <div class="contact-role">${item.title || '—'}</div>
                                    <div class="contact-owner"><span class="owner-badge">${ownerName}</span></div>
                                    <div class="contact-actions">
                                        <button class="action-btn" style="background: #17a2b8;" onclick="openContactNotes(${item.id})" title="View Notes">Notes</button>
                                        ${getActionsHtml('contact', item.id, true)}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<div class="empty-state-msg"><p>No contacts found</p><small>Add a contact using the button above, or adjust your filters.</small></div>';
    }

    function populateOpportunitiesTable() {
        const tbody = document.getElementById('opportunitiesTableBody');
        if (!tbody) return;
        const isAdmin = currentRole === 'admin';
        
        // Get status filter value
        const statusFilter = document.getElementById('opportunityStatusFilter')?.value || 'Capture';
        
        // Filter opportunities by status
        let filteredOpportunities = opportunities;
        if (statusFilter !== 'all') {
            filteredOpportunities = opportunities.filter(o => o.status === statusFilter);
        }
        
        // Get sorted data
        const sortedOpportunities = getSortedData(filteredOpportunities, 'opportunities');
        
        tbody.innerHTML = sortedOpportunities.map(item => {
            const ownerName = item.ownerDisplayName || item.ownerUsername || users.find(u => u.id == item.owner_user_id)?.display_name || users.find(u => u.id == item.owner_user_id)?.username || '—';
            const dueDateDisplay = item.dueDate ? item.dueDate : '<span style="color: #6c757d; font-style: italic;">TBD</span>';
            const isConverted = item.status === 'Converted';
            const statusBadge = isConverted 
                ? '<span class="status-badge status-converted" title="Converted to Proposal">📋 Converted</span>'
                : `<span class="status-badge status-${(item.status || '').toLowerCase().replace(' ', '')}">${item.status || ''}</span>`;
            
            // Co-owner badge with type indicator
            let coOwnerBadge = '—';
            if (item.co_owner_contact_type && item.coOwnerDisplayName) {
                const typeIcon = item.co_owner_contact_type === 'user' ? '👤' : item.co_owner_contact_type === 'federal' ? '🏛️' : '🏢';
                const typeTitle = item.co_owner_contact_type === 'user' ? 'User' : item.co_owner_contact_type === 'federal' ? 'Federal Contact' : 'Commercial Contact';
                coOwnerBadge = `<span class="owner-badge" title="${typeTitle}">${typeIcon} ${item.coOwnerDisplayName}</span>`;
            }
            
            // Archive button - uses permission system (specialty users cannot archive)
            const canArchiveOpp = !window.isSpecialty && userPermissions.opportunity?.can_archive === true;
            const archiveBtn = canArchiveOpp 
                ? `<button class="action-btn" style="background: #6c757d;" onclick="archiveOpportunity(${item.id}, '${(item.title || '').replace(/'/g, "\\'")}')" title="Archive">📦</button>`
                : '';
            
            // For converted opportunities, show view-only actions
            const actionButtons = isConverted
                ? `<button class="action-btn" style="background: #6c757d;" onclick="showToast('This opportunity has been converted to a proposal.', 'info')" title="Converted">🔒</button>${archiveBtn}`
                : `<button class="action-btn" style="background: #17a2b8;" onclick="openOpportunityNotes(${item.id})" title="View Notes">📝</button>
                   ${getActionsHtml('opportunity', item.id, true)}${archiveBtn}`;
            
            return `
                <tr class="priority-${(item.priority || 'low').toLowerCase()} ${isConverted ? 'converted-row' : ''}">
                    <td><span class="clickable-name" onclick="openOpportunityWorkspace(${item.id})">${item.title || ''}</span></td>
                    <td>${item.agencyName || '—'}</td>
                    <td>${item.division || '—'}</td>
                    <td><span class="owner-badge">👤 ${ownerName}</span></td>
                    <td>${coOwnerBadge}</td>
                    <td>$${parseFloat(item.value || 0).toLocaleString()}</td>
                    <td>${statusBadge}</td>
                    <td>${dueDateDisplay}</td>
                    <td>${item.priority || ''}</td>
                    <td>
                        <div class="action-buttons">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="10" class="empty-state-msg"><p>No opportunities found</p><small>Create one with the Add Opportunity button, or change the status filter.</small></td></tr>';
    }

    function populateProposalsTable() {
        const tbody = document.getElementById('proposalsTableBody');
        if (!tbody) return;
        
        // Get sorted data
        const sortedProposals = getSortedData(proposals, 'proposals');
        
        tbody.innerHTML = sortedProposals.map(item => {
            const ownerName = item.ownerDisplayName || item.ownerUsername || users.find(u => u.id == item.owner_user_id)?.display_name || users.find(u => u.id == item.owner_user_id)?.username || '—';
            const convertedFrom = item.converted_from_opportunity_id 
                ? `<span class="converted-badge" title="Converted from Opportunity #${item.converted_from_opportunity_id}">🔄</span>` 
                : '';
            // Archive button - uses permission system (specialty users cannot archive)
            const canArchiveProp = !window.isSpecialty && userPermissions.proposal?.can_archive === true;
            const archiveBtn = canArchiveProp 
                ? `<button class="action-btn" style="background: #6c757d;" onclick="archiveProposal(${item.id}, '${(item.title || '').replace(/'/g, "\\'")}')" title="Archive">📦</button>`
                : '';
            return `
                <tr>
                    <td><span class="clickable-name" onclick="openProposalPanel(${item.id})">${convertedFrom}${item.title || ''}</span></td>
                    <td>${item.agencyName || '—'}</td>
                    <td><span class="owner-badge">👤 ${ownerName}</span></td>
                    <td>$${parseFloat(item.value || 0).toLocaleString()}</td>
                    <td><span class="status-badge status-${(item.status || '').toLowerCase().replace(' ', '')}">${item.status || ''}</span></td>
                    <td>${item.submitDate || '—'}</td>
                    <td>${item.dueDate || '—'}</td>
                    <td>${item.validity_date || '—'}</td>
                    <td>${item.award_date || '—'}</td>
                    <td>${item.winProbability || 0}%</td>
                    <td><div class="action-buttons">${getActionsHtml('proposal', item.id)}${archiveBtn}</div></td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="11" class="empty-state">No proposals found.</td></tr>';
    }

    function populateTasksTable() {
        const tbody = document.getElementById('tasksTableBody');
        if (!tbody) return;
        
        // Get sorted data
        const sortedTasks = getSortedData(tasks, 'tasks');
        
        tbody.innerHTML = sortedTasks.map(item => {
            const assignedName = item.assignedToDisplayName || item.assignedToUsername || users.find(u => u.id == item.assigned_to_user_id)?.display_name || users.find(u => u.id == item.assigned_to_user_id)?.username || item.assignedTo || '—';
            const relatedItemName = getRelatedItemName(item.relatedTo, item.related_item_id, item.related_contact_type);
            const relatedDisplay = item.relatedTo ? `${item.relatedTo}${relatedItemName ? ': ' + relatedItemName : ''}` : '—';
            // Archive button for admin and user roles (not manager or specialty)
            const canArchiveTask = !window.isSpecialty && (currentRole === 'admin' || currentRole === 'user');
            const archiveBtn = canArchiveTask 
                ? `<button class="action-btn" style="background: #6c757d;" onclick="archiveTask(${item.id}, '${(item.title || '').replace(/'/g, "\\'")}')" title="Archive">📦</button>`
                : '';
            return `
                <tr class="priority-${(item.priority || 'low').toLowerCase()}">
                    <td>${item.title || ''}</td>
                    <td>${relatedDisplay}</td>
                    <td>${item.dueDate || ''}</td>
                    <td>${item.priority || ''}</td>
                    <td><span class="status-badge status-${(item.status || '').toLowerCase().replace(' ', '')}">${item.status || ''}</span></td>
                    <td>${assignedName}</td>
                    <td><div class="action-buttons">${getActionsHtml('task', item.id)}${archiveBtn}</div></td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="7" class="empty-state">No tasks found.</td></tr>';
    }
    
    // Get related item name for display
    function getRelatedItemName(relatedTo, relatedItemId, relatedContactType = '') {
        if (!relatedTo || !relatedItemId) return '';
        
        // Normalize case
        const normalizedType = (relatedTo || '').charAt(0).toUpperCase() + (relatedTo || '').slice(1).toLowerCase();
        
        switch(normalizedType) {
            case 'Agency':
                const agency = agencies.find(a => a.id == relatedItemId);
                return agency ? agency.name : '';
            case 'Contact':
                // Check contact type to determine which array to search
                if (relatedContactType === 'commercial') {
                    const companyContact = companyContacts.find(c => c.id == relatedItemId);
                    return companyContact ? `${companyContact.first_name} ${companyContact.last_name}` : '';
                } else {
                    // Default to federal contacts for backwards compatibility
                    const contact = contacts.find(c => c.id == relatedItemId);
                    return contact ? `${contact.firstName} ${contact.lastName}` : '';
                }
            case 'Opportunity':
                const opp = opportunities.find(o => o.id == relatedItemId);
                return opp ? opp.title : '';
            case 'Proposal':
                const prop = proposals.find(p => p.id == relatedItemId);
                return prop ? prop.title : '';
            case 'Event':
                const evt = (events || []).find(e => e.id == relatedItemId);
                return evt ? evt.name : '';
            default:
                return '';
        }
    }
    
    // Get tooltip text for calendar task hover
    function getTaskTooltip(task) {
        const relatedName = getRelatedItemName(task.relatedTo, task.related_item_id, task.related_contact_type);
        let tooltip = task.title;
        if (relatedName) {
            tooltip += `\n${task.relatedTo}: ${relatedName}`;
        }
        tooltip += `\nPriority: ${task.priority || 'N/A'}`;
        tooltip += `\nStatus: ${task.status || 'N/A'}`;
        if (task.assignedTo) tooltip += `\nAssigned: ${task.assignedTo}`;
        return tooltip;
    }
    
    // Event helper functions for calendar
    function getEventTooltip(event) {
        let tooltip = `[${event.eventType.toUpperCase()}] ${event.title}`;
        if (event.eventType === 'task') {
            const relatedName = getRelatedItemName(event.relatedTo, event.related_item_id, event.related_contact_type);
            if (relatedName) tooltip += `\n${event.relatedTo}: ${relatedName}`;
            tooltip += `\nPriority: ${event.priority || 'N/A'}`;
            tooltip += `\nStatus: ${event.status || 'N/A'}`;
        } else if (event.eventType === 'proposal-submit' || event.eventType === 'proposal-due') {
            if (event.agencyName) tooltip += `\nAgency: ${event.agencyName}`;
            tooltip += `\nStatus: ${event.status || 'N/A'}`;
            tooltip += `\nValue: $${parseFloat(event.value || 0).toLocaleString()}`;
            tooltip += `\nWin Probability: ${event.winProbability || 0}%`;
            if (event.submitDate) tooltip += `\nSubmit Date: ${event.submitDate}`;
            if (event.dueDate) tooltip += `\nDue Date: ${event.dueDate}`;
        } else if (event.eventType === 'opportunity') {
            if (event.agencyName) tooltip += `\nAgency: ${event.agencyName}`;
            tooltip += `\nStatus: ${event.status || 'N/A'}`;
            tooltip += `\nValue: $${parseFloat(event.value || 0).toLocaleString()}`;
            tooltip += `\nPriority: ${event.priority || 'N/A'}`;
        } else if (event.eventType === 'crm-event') {
            tooltip += `\nType: ${event.event_type || 'N/A'}`;
            tooltip += `\nStatus: ${event.status || 'N/A'}`;
            if (event.location) tooltip += `\nLocation: ${event.location}`;
            if (event.start_datetime) tooltip += `\nStart: ${formatEventDateTime(event.start_datetime)}`;
            if (event.end_datetime) tooltip += `\nEnd: ${formatEventDateTime(event.end_datetime)}`;
        }
        return tooltip;
    }
    
    function getEventClickHandler(event) {
        if (event.eventType === 'task') {
            return `showTaskDetail(${event.id})`;
        } else if (event.eventType === 'proposal-submit' || event.eventType === 'proposal-due') {
            return `openProposalPanel(${event.id})`;
        } else if (event.eventType === 'opportunity') {
            return `openOpportunityWorkspace(${event.id})`;
        } else if (event.eventType === 'crm-event') {
            return `viewEvent(${event.id})`;
        }
        return '';
    }
    
    function getEventDblClickHandler(event) {
        if (event.eventType === 'task') {
            return `editItem('task', ${event.id})`;
        } else if (event.eventType === 'proposal-submit' || event.eventType === 'proposal-due') {
            return `editItem('proposal', ${event.id})`;
        } else if (event.eventType === 'opportunity') {
            return `editItem('opportunity', ${event.id})`;
        } else if (event.eventType === 'crm-event') {
            return `editEvent(${event.id})`;
        }
        return '';
    }
    
    function getEventMetaInfo(event) {
        if (event.eventType === 'task') {
            const relatedName = getRelatedItemName(event.relatedTo, event.related_item_id, event.related_contact_type);
            const relatedDisplay = event.relatedTo + (relatedName ? `: ${relatedName}` : '');
            return `${event.status} • ${relatedDisplay}`;
        } else if (event.eventType === 'proposal-submit') {
            return `${event.status} • ${event.agencyName || 'N/A'} • Submit Date`;
        } else if (event.eventType === 'proposal-due') {
            return `${event.status} • ${event.agencyName || 'N/A'} • Due Date`;
        } else if (event.eventType === 'opportunity') {
            return `${event.status} • ${event.agencyName || 'N/A'} • $${parseFloat(event.value || 0).toLocaleString()}`;
        } else if (event.eventType === 'crm-event') {
            return `${event.status} • ${event.event_type || 'Event'} • ${event.location || 'No location'}`;
        }
        return '';
    }
    
    function getEventDetailInfo(event) {
        if (event.eventType === 'task') {
            const relatedName = getRelatedItemName(event.relatedTo, event.related_item_id, event.related_contact_type);
            return event.relatedTo + (relatedName ? `: ${relatedName}` : '');
        } else if (event.eventType === 'proposal-submit') {
            return `Agency: ${event.agencyName || 'N/A'} • Value: $${parseFloat(event.value || 0).toLocaleString()} • Submit Date`;
        } else if (event.eventType === 'proposal-due') {
            return `Agency: ${event.agencyName || 'N/A'} • Value: $${parseFloat(event.value || 0).toLocaleString()} • Due Date`;
        } else if (event.eventType === 'opportunity') {
            return `Agency: ${event.agencyName || 'N/A'} • Value: $${parseFloat(event.value || 0).toLocaleString()}`;
        } else if (event.eventType === 'crm-event') {
            return `Type: ${event.event_type || 'Event'} • ${event.location || 'No location'}`;
        }
        return '';
    }
    
    function getEventBadgeInfo(event) {
        if (event.eventType === 'task') {
            const assignedName = event.assignedToDisplayName || event.assignedToUsername || users.find(u => u.id == event.assigned_to_user_id)?.display_name || users.find(u => u.id == event.assigned_to_user_id)?.username || event.assignedTo || 'Unassigned';
            return `
                <span class="task-badge status">${event.status}</span>
                <span class="task-badge category">${event.relatedTo || 'General'}</span>
                <span class="task-badge status">👤 ${assignedName}</span>
            `;
        } else if (event.eventType === 'proposal-submit') {
            return `
                <span class="task-badge status">${event.status}</span>
                <span class="task-badge category" style="background: rgba(40,167,69,0.1); color: #28a745;">📤 Submit</span>
                <span class="task-badge status">${event.winProbability || 0}% Win</span>
            `;
        } else if (event.eventType === 'proposal-due') {
            return `
                <span class="task-badge status">${event.status}</span>
                <span class="task-badge category" style="background: rgba(220,53,69,0.1); color: #dc3545;">📅 Due</span>
                <span class="task-badge status">${event.winProbability || 0}% Win</span>
            `;
        } else if (event.eventType === 'opportunity') {
            return `
                <span class="task-badge status">${event.status}</span>
                <span class="task-badge category">💼 Opportunity</span>
                <span class="task-badge status">${event.priority || 'Medium'} Priority</span>
            `;
        } else if (event.eventType === 'crm-event') {
            const typeIcons = { 'Meeting': '🤝', 'Conference': '🏛️', 'Training': '📚', 'Webinar': '💻', 'Site Visit': '🏢', 'Review': '📋', 'Workshop': '🔧', 'Other': '📅' };
            return `
                <span class="task-badge status">${event.status}</span>
                <span class="task-badge category" style="background: rgba(102,126,234,0.1); color: #667eea;">${typeIcons[event.event_type] || '📅'} ${event.event_type || 'Event'}</span>
                <span class="task-badge status">${event.priority || 'Medium'} Priority</span>
            `;
        }
        return '';
    }
    
    // Show proposal detail in a panel (similar to task detail)
    function showProposalDetail(proposalId) {
        const proposal = proposals.find(p => p.id == proposalId);
        if (!proposal) return;
        
        // Use the task panel for proposals as well
        document.getElementById('taskPanelTitle').textContent = proposal.title;
        document.getElementById('taskPanelDate').textContent = proposal.dueDate ? `Due: ${new Date(proposal.dueDate + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}` : '';
        
        const ownerName = proposal.ownerDisplayName || proposal.ownerUsername || users.find(u => u.id == proposal.owner_user_id)?.display_name || users.find(u => u.id == proposal.owner_user_id)?.username || 'Unassigned';
        
        let bodyHtml = `
            <div class="task-panel-row">
                <div class="task-panel-label">Type</div>
                <div class="task-panel-value"><span class="event-type-badge proposal-submit" style="background: #28a745;">PROPOSAL</span></div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Status</div>
                <div class="task-panel-value"><span class="status-badge status-${(proposal.status || '').toLowerCase().replace(' ', '')}">${proposal.status}</span></div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Agency</div>
                <div class="task-panel-value">${proposal.agencyName || '—'}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Value</div>
                <div class="task-panel-value">$${parseFloat(proposal.value || 0).toLocaleString()}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Win Probability</div>
                <div class="task-panel-value">${proposal.winProbability || 0}%</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Owner</div>
                <div class="task-panel-value">${ownerName}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Submit Date</div>
                <div class="task-panel-value" style="color: #28a745;">${proposal.submitDate || '—'}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Due Date</div>
                <div class="task-panel-value" style="color: #dc3545;">${proposal.dueDate || '—'}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Description</div>
                <div class="task-panel-value">${proposal.description || 'No description provided.'}</div>
            </div>
        `;
        
        document.getElementById('taskPanelBody').innerHTML = bodyHtml;
        
        // Update edit button to edit proposal
        const editBtn = document.querySelector('#taskDetailPanel .btn');
        if (editBtn) {
            editBtn.onclick = () => {
                closeTaskPanel();
                editItem('proposal', proposalId);
            };
        }
        
        document.getElementById('taskDetailPanel').classList.add('open');
        document.getElementById('taskPanelOverlay').classList.add('open');
    }

    // Load My Tasks dashboard
    async function loadMyTasks() {
        if (!userPermissions.mytasks?.can_view) return;
        
        try {
            const response = await fetch(`${API_URL}?action=getMyTasks`);
            const data = await response.json();
            
            const summary = data.summary || {};
            document.getElementById('myTasksSummary').innerHTML = `
                <div class="dashboard-card"><h3>${summary.tasks || 0}</h3><p>Active Tasks</p></div>
                <div class="dashboard-card"><h3>${summary.opportunities || 0}</h3><p>Open Opportunities</p></div>
                <div class="dashboard-card"><h3>${summary.proposals || 0}</h3><p>Active Proposals</p></div>
            `;
            
            let content = '';
            
            // Tasks section
            content += `<div class="section-card">
                <div class="section-header">
                    <div class="section-title"><span>📝 Tasks</span><span class="section-count">${(data.tasks || []).length}</span></div>
                    <button class="link-btn" onclick="showTab('tasks', document.querySelector('[data-tab-for=task]'))">View All →</button>
                </div>`;
            if ((data.tasks || []).length === 0) {
                content += '<div class="empty-state">✓ All tasks completed!</div>';
            } else {
                content += '<table class="data-table"><thead><tr><th>Task</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                (data.tasks || []).slice(0, 5).forEach(t => {
                    content += `<tr class="priority-${(t.priority || 'low').toLowerCase()}">
                        <td><strong>${t.title || ''}</strong></td>
                        <td>${t.dueDate || ''}</td>
                        <td>${t.priority || ''}</td>
                        <td><span class="status-badge status-${(t.status || '').toLowerCase().replace(' ', '')}">${t.status || ''}</span></td>
                        <td><button class="action-btn complete" onclick="completeTask(${t.id})">✓ Complete</button></td>
                    </tr>`;
                });
                content += '</tbody></table>';
            }
            content += '</div>';

            // Opportunities section
            content += `<div class="section-card">
                <div class="section-header">
                    <div class="section-title"><span>🎯 Open Opportunities</span><span class="section-count">${(data.opportunities || []).length}</span></div>
                    <button class="link-btn" onclick="showTab('opportunities', document.querySelector('[data-tab-for=opportunity]'))">View All →</button>
                </div>`;
            if ((data.opportunities || []).length === 0) {
                content += '<div class="empty-state">No open opportunities.</div>';
            } else {
                content += '<table class="data-table"><thead><tr><th>Opportunity</th><th>Agency</th><th>Value</th><th>Due Date</th></tr></thead><tbody>';
                (data.opportunities || []).slice(0, 3).forEach(o => {
                    content += `<tr class="priority-${(o.priority || 'low').toLowerCase()}">
                        <td><strong>${o.title || ''}</strong></td>
                        <td>${o.agencyName || ''}</td>
                        <td>$${parseFloat(o.value || 0).toLocaleString()}</td>
                        <td>${o.dueDate || ''}</td>
                    </tr>`;
                });
                content += '</tbody></table>';
            }
            content += '</div>';
            
            // Proposals section
            content += `<div class="section-card">
                <div class="section-header">
                    <div class="section-title"><span>📄 Active Proposals</span><span class="section-count">${(data.proposals || []).length}</span></div>
                    <button class="link-btn" onclick="showTab('proposals', document.querySelector('[data-tab-for=proposal]'))">View All →</button>
                </div>`;
            if ((data.proposals || []).length === 0) {
                content += '<div class="empty-state">No active proposals.</div>';
            } else {
                content += '<table class="data-table"><thead><tr><th>Proposal</th><th>Agency</th><th>Value</th><th>Status</th></tr></thead><tbody>';
                (data.proposals || []).slice(0, 3).forEach(p => {
                    content += `<tr>
                        <td><strong>${p.title || ''}</strong></td>
                        <td>${p.agencyName || ''}</td>
                        <td>$${parseFloat(p.value || 0).toLocaleString()}</td>
                        <td><span class="status-badge status-${(p.status || '').toLowerCase().replace(' ', '')}">${p.status || ''}</span></td>
                    </tr>`;
                });
                content += '</tbody></table>';
            }
            content += '</div>';
            
            document.getElementById('myTasksContent').innerHTML = content;
            
        } catch (error) {
            console.error('Error loading my tasks:', error);
        }
    }

    // Complete task
    async function completeTask(taskId) {
        try {
            const response = await fetch(`${API_URL}?action=quickUpdateTask`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: taskId, status: 'Done' })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Failed to complete task');
            }
            
            await fetchAllData();
            // Reload My Tasks page to reflect the change
            await loadMyTasks();
        } catch (error) {
            showToast('Error completing task: ' + error.message, 'error');
        }
    }



    // Show contact detail modal
    function showContactDetail(contactId) {
        openContactPanel(contactId);
    }

    // Filter contacts by agency
    function filterContacts() {
        populateContactsTable();
    }

    // Tab navigation
    function showTab(tabName, element) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.getElementById(tabName)?.classList.add('active');
        document.querySelectorAll('.nav-tab').forEach(btn => btn.classList.remove('active'));
        element?.classList.add('active');
        
        if (tabName === 'mytasks') loadMyTasks();
        if (tabName === 'contacts') populateContactsTable();
        if (tabName === 'calendar') renderCalendar();
        if (tabName === 'kanban') renderKanbanBoard();
        if (tabName === 'deptcalendar') initDeptCalendar();
    }

    // Navigate to a tab from dashboard charts
    function navigateToTab(tabName) {
        // Map tab content ID to data-tab-for value
        const tabMap = {
            'opportunities': 'opportunity',
            'proposals': 'proposal',
            'agencies': 'agency',
            'contacts': 'contact',
            'tasks': 'task'
        };
        const tabFor = tabMap[tabName] || tabName;
        const navTab = document.querySelector(`.nav-tab[data-tab-for="${tabFor}"]`);
        if (navTab) {
            showTab(tabName, navTab);
            // Scroll to top of page
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Modal functions
    function openModal(modalId) {
        // Reset form if opening for new item (not editing)
        if (!currentEditId) {
            const form = document.querySelector(`#${modalId} form`);
            if (form) form.reset();
            
            // Clear datalists
            if (modalId === 'contactModal') {
                document.getElementById('contactDivisionList').innerHTML = '';
                document.getElementById('contactModalTitle').textContent = 'Add Contact';
            } else if (modalId === 'opportunityModal') {
                document.getElementById('opportunityDivisionList').innerHTML = '';
                document.getElementById('opportunityModalTitle').textContent = 'Add Opportunity';
                document.getElementById('opportunityDueDateTBD').checked = false;
                document.getElementById('opportunityDueDate').disabled = false;
                // Reset co-owner fields
                document.getElementById('opportunityCoOwnerType').value = '';
                document.getElementById('opportunityCoOwner').value = '';
                document.getElementById('coOwnerSelectGroup').style.display = 'none';
                // Hide linked contacts section for new opportunities
                document.getElementById('opportunityContactsSection').style.display = 'none';
                // Hide documents section for new opportunities
                document.getElementById('opportunityDocumentsSection').style.display = 'none';
                document.getElementById('opportunityDocumentsList').innerHTML = '';
            } else if (modalId === 'proposalModal') {
                document.getElementById('proposalModalTitle').textContent = 'Add Proposal';
                // Hide linked contacts section for new proposals
                document.getElementById('proposalContactsSection').style.display = 'none';
                // Hide documents section for new proposals
                document.getElementById('proposalDocumentsSection').style.display = 'none';
                document.getElementById('proposalDocumentsList').innerHTML = '';
            }
        }
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        currentEditId = null;
        // Reset TBD checkbox when closing opportunity modal
        if (modalId === 'opportunityModal') {
            document.getElementById('opportunityDueDateTBD').checked = false;
            document.getElementById('opportunityDueDate').disabled = false;
            document.getElementById('opportunityDueDate').value = '';
        }
    }
    
    // Close topmost open modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal');
            for (let i = openModals.length - 1; i >= 0; i--) {
                if (openModals[i].style.display === 'block') {
                    closeModal(openModals[i].id);
                    return;
                }
            }
        }
    });

    // Toggle TBD for opportunity due date
    function toggleOpportunityTBD() {
        const tbdCheckbox = document.getElementById('opportunityDueDateTBD');
        const dueDateInput = document.getElementById('opportunityDueDate');
        
        if (tbdCheckbox.checked) {
            dueDateInput.value = '';
            dueDateInput.disabled = true;
        } else {
            dueDateInput.disabled = false;
            dueDateInput.focus();
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            currentEditId = null;
        }
    };

    // Edit item
    function editItem(type, id) {
        const dataMap = { agency: agencies, contact: contacts, opportunity: opportunities, proposal: proposals, task: tasks };
        const item = dataMap[type]?.find(i => i.id == id);
        if (!item) return;
        
        currentEditId = id;
        document.getElementById(`${type}ModalTitle`).textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        document.getElementById(`${type}Id`).value = id;
        
        // Populate form fields based on type
        if (type === 'agency') {
            document.getElementById('agencyName').value = item.name || '';
            document.getElementById('agencyType').value = item.type || '';
            document.getElementById('agencyLocation').value = item.location || '';
            document.getElementById('agencyStatus').value = item.status || 'Active';
            document.getElementById('agencyDescription').value = item.description || '';
        } else if (type === 'contact') {
            document.getElementById('contactFirstName').value = item.firstName || '';
            document.getElementById('contactLastName').value = item.lastName || '';
            document.getElementById('contactTitle').value = item.title || '';
            document.getElementById('contactAgency').value = item.agency_id || '';
            // Load divisions for the selected agency, then set the division value
            loadDivisionsForAgency('contact').then(() => {
                document.getElementById('contactDivision').value = item.division || '';
            });
            document.getElementById('contactOwner').value = item.owner_user_id || '';
            document.getElementById('contactEmail').value = item.email || '';
            document.getElementById('contactPhone').value = item.phone || '';
            document.getElementById('contactStatus').value = item.status || 'Active';
            document.getElementById('contactNotes').value = item.notes || '';
        } else if (type === 'opportunity') {
            document.getElementById('opportunityTitle').value = item.title || '';
            document.getElementById('opportunityAgency').value = item.agency_id || '';
            // Load divisions for the selected agency, then set the division value
            loadDivisionsForAgency('opportunity').then(() => {
                document.getElementById('opportunityDivision').value = item.division || '';
            });
            document.getElementById('opportunityOwner').value = item.owner_user_id || '';
            
            // Set co-owner fields
            document.getElementById('opportunityCoOwnerType').value = item.co_owner_contact_type || '';
            loadCoOwnerOptions(); // Populate co-owner dropdown based on type
            if (item.co_owner_contact_id) {
                document.getElementById('opportunityCoOwner').value = item.co_owner_contact_id;
            }
            
            document.getElementById('opportunityValue').value = item.value || '';
            
            // Store original status for tracking changes to "Bid"
            originalOpportunityStatus = item.status || 'Lead';
            document.getElementById('opportunityStatus').value = item.status || 'Lead';
            
            // If status is "Converted", show read-only message
            if (item.status === 'Converted') {
                showToast('This opportunity has been converted to a proposal and cannot be edited.', 'info');
                return; // Don't open the modal
            }
            
            // Handle TBD checkbox for due date
            const hasDueDate = item.dueDate && item.dueDate !== '';
            document.getElementById('opportunityDueDateTBD').checked = !hasDueDate;
            document.getElementById('opportunityDueDate').value = item.dueDate || '';
            document.getElementById('opportunityDueDate').disabled = !hasDueDate;
            document.getElementById('opportunityPriority').value = item.priority || 'Medium';
            document.getElementById('opportunityDescription').value = item.description || '';
            
            // Show linked contacts section and render contacts
            document.getElementById('opportunityContactsSection').style.display = 'block';
            renderOpportunityLinkedContacts(id);
            
            // Show documents section and render documents
            document.getElementById('opportunityDocumentsSection').style.display = 'block';
            loadOpportunityDocuments(id);
        } else if (type === 'proposal') {
            document.getElementById('proposalTitle').value = item.title || '';
            document.getElementById('proposalAgency').value = item.agency_id || '';
            document.getElementById('proposalOwner').value = item.owner_user_id || '';
            document.getElementById('proposalValue').value = item.value || '';
            document.getElementById('proposalStatus').value = item.status || 'Draft';
            document.getElementById('proposalSubmitDate').value = item.submitDate || '';
            document.getElementById('proposalDueDate').value = item.dueDate || '';
            document.getElementById('proposalValidityDate').value = item.validity_date || '';
            document.getElementById('proposalAwardDate').value = item.award_date || '';
            document.getElementById('proposalWinProbability').value = item.winProbability || 0;
            document.getElementById('proposalDescription').value = item.description || '';
            
            // Show linked contacts section and render contacts
            document.getElementById('proposalContactsSection').style.display = 'block';
            renderProposalLinkedContacts(id);
            
            // Show documents section and render documents
            document.getElementById('proposalDocumentsSection').style.display = 'block';
            loadProposalDocuments(id);
        } else if (type === 'task') {
            document.getElementById('taskTitle').value = item.title || '';
            // Normalize relatedTo case (database might have lowercase values)
            let relatedTo = item.relatedTo || '';
            if (relatedTo) {
                // Capitalize first letter to match dropdown values
                relatedTo = relatedTo.charAt(0).toUpperCase() + relatedTo.slice(1).toLowerCase();
            }
            document.getElementById('taskRelatedTo').value = relatedTo;
            document.getElementById('taskDueDate').value = item.dueDate || '';
            document.getElementById('taskPriority').value = item.priority || 'Medium';
            document.getElementById('taskStatus').value = item.status || 'To Do';
            document.getElementById('taskAssignedTo').value = item.assigned_to_user_id || '';
            document.getElementById('taskDescription').value = item.description || '';
            document.getElementById('taskRelatedItemId').value = item.related_item_id || '';
            document.getElementById('taskRelatedContactType').value = item.related_contact_type || '';
            
            // Update related item display (preserve selection since we're editing)
            updateRelatedItemOptions(true);
            if (item.related_item_id && relatedTo) {
                selectRelatedItemById(relatedTo, item.related_item_id, item.related_contact_type);
            }
        }
        
        openModal(`${type}Modal`);
    }

    // Archive Opportunity (Admin only)
    async function archiveOpportunity(id, title) {
        if (!confirm(`Are you sure you want to archive "${title}"?\n\nThis will move the opportunity and all related tasks to the archive. You can restore it later from the Admin Panel.`)) return;
        
        try {
            const response = await fetch(`${API_URL}?action=archiveOpportunity`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('Opportunity archived successfully.', 'success');
                await fetchAllData();
            } else {
                showToast('Error: ' + (data.error || 'Failed to archive opportunity', 'error'));
            }
        } catch (error) {
            console.error('Error archiving opportunity:', error);
            showToast('Error archiving opportunity. Please try again.', 'error');
        }
    }

    // Archive Proposal (Admin only)
    async function archiveProposal(id, title) {
        if (!confirm(`Are you sure you want to archive "${title}"?\n\nThis will move the proposal and all related tasks to the archive. You can restore it later from the Admin Panel.`)) return;
        
        try {
            const response = await fetch(`${API_URL}?action=archiveProposal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('Proposal archived successfully.', 'success');
                await fetchAllData();
            } else {
                showToast('Error: ' + (data.error || 'Failed to archive proposal', 'error'));
            }
        } catch (error) {
            console.error('Error archiving proposal:', error);
            showToast('Error archiving proposal. Please try again.', 'error');
        }
    }

    // Archive Task (Admin and User roles only)
    async function archiveTask(id, title) {
        if (!confirm(`Are you sure you want to archive "${title}"?\n\nThis will move the task to the archive. An admin can restore it later from the Admin Panel.`)) return;
        
        try {
            const response = await fetch(`${API_URL}?action=archiveTask`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const data = await response.json();
            
            if (data.success) {
                showToast('Task archived successfully.', 'success');
                await fetchAllData();
            } else {
                showToast('Error: ' + (data.error || 'Failed to archive task', 'error'));
            }
        } catch (error) {
            console.error('Error archiving task:', error);
            showToast('Error archiving task. Please try again.', 'error');
        }
    }

    // Delete item
    async function deleteItem(type, id) {
        // Special spicy message for tasks
        if (type === 'task') {
            if (!confirm('Are you sure you are spicy enough to delete this?')) return;
        } else {
            if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=delete&type=${type}&id=${id}`, { method: 'POST' });
            if (!response.ok) throw new Error('Delete failed');
            await fetchAllData();
        } catch (error) {
            showToast(`Error deleting ${type}: ${error.message}`, 'error');
        }
    }

    // Save functions for each type
    async function saveData(type, data) {
        const modal = document.getElementById(`${type}Modal`);
        const submitBtn = modal?.querySelector('button[type="submit"], .btn');
        const originalText = submitBtn?.textContent;
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving...'; submitBtn.style.opacity = '0.7'; }
        try {
            const response = await fetch(`${API_URL}?action=save${type.charAt(0).toUpperCase() + type.slice(1)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.error || 'Save failed');
            }
            await fetchAllData();
            closeModal(`${type}Modal`);
        } catch (error) {
            showToast(`Error saving ${type}: ${error.message}`, 'error');
        } finally {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalText; submitBtn.style.opacity = ''; }
        }
    }

    // Form submissions
    document.getElementById('agencyForm')?.addEventListener('submit', e => {
        e.preventDefault();
        // Use hidden input value as fallback if currentEditId is not set
        const agencyIdValue = document.getElementById('agencyId').value;
        const effectiveId = currentEditId || (agencyIdValue ? parseInt(agencyIdValue) : null);
        saveData('agency', {
            id: effectiveId,
            name: document.getElementById('agencyName').value,
            type: document.getElementById('agencyType').value,
            location: document.getElementById('agencyLocation').value,
            status: document.getElementById('agencyStatus').value,
            description: document.getElementById('agencyDescription').value
        });
    });

    document.getElementById('contactForm')?.addEventListener('submit', e => {
        e.preventDefault();
        // Use hidden input value as fallback if currentEditId is not set
        const contactIdValue = document.getElementById('contactId').value;
        const effectiveId = currentEditId || (contactIdValue ? parseInt(contactIdValue) : null);
        saveData('contact', {
            id: effectiveId,
            firstName: document.getElementById('contactFirstName').value,
            lastName: document.getElementById('contactLastName').value,
            title: document.getElementById('contactTitle').value,
            division: document.getElementById('contactDivision').value,
            agency_id: document.getElementById('contactAgency').value,
            owner_user_id: document.getElementById('contactOwner').value || null,
            email: document.getElementById('contactEmail').value,
            phone: document.getElementById('contactPhone').value,
            status: document.getElementById('contactStatus').value,
            notes: document.getElementById('contactNotes').value
        });
    });

    // Track original status for detecting "Bid" selection
    let originalOpportunityStatus = '';
    let pendingConversionOpportunityId = null;

    // Handle opportunity status change
    function handleOpportunityStatusChange() {
        const newStatus = document.getElementById('opportunityStatus').value;
        const opportunityId = document.getElementById('opportunityId').value;
        
        // If changing to "Bid" on an existing opportunity, show conversion dialog
        if (newStatus === 'Bid' && opportunityId && originalOpportunityStatus !== 'Bid') {
            // Check permissions first
            if (!userPermissions.opportunity?.can_update || !userPermissions.proposal?.can_create) {
                showToast('You need both Opportunity edit and Proposal create permissions to convert.', 'warning');
                document.getElementById('opportunityStatus').value = originalOpportunityStatus || 'Lead';
                return;
            }
            
            if (confirm('Setting status to "Bid" will convert this Opportunity to a Proposal. Continue?')) {
                showConvertToProposalModal(opportunityId);
            } else {
                // Reset to original status
                document.getElementById('opportunityStatus').value = originalOpportunityStatus || 'Lead';
            }
        }
    }

    // Show the Convert to Proposal modal
    function showConvertToProposalModal(opportunityId) {
        const opportunity = opportunities.find(o => o.id == opportunityId);
        if (!opportunity) {
            showToast('Opportunity not found.', 'warning');
            return;
        }
        
        // Close the opportunity modal
        closeModal('opportunityModal');
        
        // Store the opportunity ID
        pendingConversionOpportunityId = opportunityId;
        document.getElementById('convertOpportunityId').value = opportunityId;
        
        // Show opportunity info
        document.getElementById('convertOpportunityInfo').innerHTML = `
            <strong>${opportunity.title}</strong><br>
            <span style="color: #6c757d;">Agency: ${opportunity.agencyName || 'N/A'}</span><br>
            <span style="color: #6c757d;">Value: $${parseFloat(opportunity.value || 0).toLocaleString()}</span>
        `;
        
        // Set default dates
        document.getElementById('convertSubmitDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('convertDueDate').value = opportunity.dueDate || '';
        document.getElementById('convertWinProbability').value = 50;
        
        // Show the modal
        document.getElementById('convertToProposalModal').style.display = 'block';
    }

    // Cancel conversion
    function cancelConvertToProposal() {
        document.getElementById('convertToProposalModal').style.display = 'none';
        pendingConversionOpportunityId = null;
    }

    // Submit conversion
    async function submitConvertToProposal(e) {
        e.preventDefault();
        
        const opportunityId = document.getElementById('convertOpportunityId').value;
        const winProbability = document.getElementById('convertWinProbability').value;
        const submitDate = document.getElementById('convertSubmitDate').value;
        const dueDate = document.getElementById('convertDueDate').value;
        
        try {
            const response = await fetch(`${API_URL}?action=convertOpportunityToProposal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: opportunityId,
                    winProbability: winProbability,
                    submitDate: submitDate,
                    dueDate: dueDate
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Opportunity successfully converted to Proposal!', 'success');
                document.getElementById('convertToProposalModal').style.display = 'none';
                pendingConversionOpportunityId = null;
                
                // Refresh data and switch to proposals tab
                await fetchAllData();
                
                // Optionally switch to proposals tab
                const proposalsTab = document.querySelector('[data-tab-for="proposal"]');
                if (proposalsTab) {
                    showTab('proposals', proposalsTab);
                }
            } else {
                showToast('Error: ' + (result.error || 'Failed to convert opportunity', 'error'));
            }
        } catch (error) {
            console.error('Error converting opportunity:', error);
            showToast('Error converting opportunity. Please try again.', 'error');
        }
    }

    document.getElementById('opportunityForm')?.addEventListener('submit', e => {
        e.preventDefault();
        
        const status = document.getElementById('opportunityStatus').value;
        const opportunityId = document.getElementById('opportunityId').value;
        
        // Use hidden input value as fallback if currentEditId is not set
        const effectiveId = currentEditId || (opportunityId ? parseInt(opportunityId) : null);
        
        // If status is "Bid" on a NEW opportunity, warn user
        if (status === 'Bid' && !effectiveId) {
            showToast('Please save the opportunity first, then change status to Bid to convert it to a Proposal.', 'warning');
            return;
        }
        
        // If status is "Bid" on existing opportunity, trigger conversion flow
        if (status === 'Bid' && effectiveId && originalOpportunityStatus !== 'Bid') {
            showConvertToProposalModal(effectiveId);
            return;
        }
        
        // Don't allow editing converted opportunities
        if (status === 'Converted') {
            showToast('Converted opportunities cannot be edited.', 'info');
            return;
        }
        
        const isTBD = document.getElementById('opportunityDueDateTBD').checked;
        // Validate: if not TBD, due date is required
        if (!isTBD && !document.getElementById('opportunityDueDate').value) {
            showToast('Please enter a Due Date or check TBD.', 'warning');
            return;
        }
        
        // Get co-owner data
        const coOwnerType = document.getElementById('opportunityCoOwnerType').value || null;
        const coOwnerId = document.getElementById('opportunityCoOwner').value || null;
        
        saveData('opportunity', {
            id: effectiveId,
            title: document.getElementById('opportunityTitle').value,
            agency_id: document.getElementById('opportunityAgency').value,
            division: document.getElementById('opportunityDivision').value || null,
            owner_user_id: document.getElementById('opportunityOwner').value || null,
            co_owner_contact_id: coOwnerId,
            co_owner_contact_type: coOwnerType,
            value: document.getElementById('opportunityValue').value,
            status: document.getElementById('opportunityStatus').value,
            dueDate: isTBD ? null : document.getElementById('opportunityDueDate').value,
            priority: document.getElementById('opportunityPriority').value,
            description: document.getElementById('opportunityDescription').value
        });
    });

    document.getElementById('proposalForm')?.addEventListener('submit', e => {
        e.preventDefault();
        // Use hidden input value as fallback if currentEditId is not set
        const proposalIdValue = document.getElementById('proposalId').value;
        const effectiveId = currentEditId || (proposalIdValue ? parseInt(proposalIdValue) : null);
        saveData('proposal', {
            id: effectiveId,
            title: document.getElementById('proposalTitle').value,
            agency_id: document.getElementById('proposalAgency').value,
            owner_user_id: document.getElementById('proposalOwner').value || null,
            value: document.getElementById('proposalValue').value,
            status: document.getElementById('proposalStatus').value,
            submitDate: document.getElementById('proposalSubmitDate').value || null,
            dueDate: document.getElementById('proposalDueDate').value,
            validityDate: document.getElementById('proposalValidityDate').value || null,
            awardDate: document.getElementById('proposalAwardDate').value || null,
            winProbability: document.getElementById('proposalWinProbability').value,
            description: document.getElementById('proposalDescription').value
        });
    });

    document.getElementById('taskForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        // Use hidden input value as fallback if currentEditId is not set
        const taskIdValue = document.getElementById('taskId').value;
        const effectiveId = currentEditId || (taskIdValue ? parseInt(taskIdValue) : null);
        const taskData = {
            id: effectiveId,
            title: document.getElementById('taskTitle').value,
            relatedTo: document.getElementById('taskRelatedTo').value,
            related_item_id: document.getElementById('taskRelatedItemId').value || null,
            related_contact_type: document.getElementById('taskRelatedContactType').value || null,
            dueDate: document.getElementById('taskDueDate').value,
            priority: document.getElementById('taskPriority').value,
            status: document.getElementById('taskStatus').value,
            assigned_to_user_id: document.getElementById('taskAssignedTo').value || null,
            assignedTo: users.find(u => u.id == document.getElementById('taskAssignedTo').value)?.display_name || users.find(u => u.id == document.getElementById('taskAssignedTo').value)?.username || '',
            description: document.getElementById('taskDescription').value,
            workspace_phase: document.getElementById('taskWorkspacePhase').value || null
        };

        // Optimistic update: show task in workspace table immediately
        if (currentWorkspaceOppId && (taskData.relatedTo || '').toLowerCase() === 'opportunity' && taskData.related_item_id == currentWorkspaceOppId) {
            const tempTask = { ...taskData, assigned_to_name: taskData.assignedTo, id: effectiveId || 'temp_' + Date.now() };
            if (effectiveId) {
                const idx = workspaceData.tasks.findIndex(t => t.id == effectiveId);
                if (idx >= 0) workspaceData.tasks[idx] = { ...workspaceData.tasks[idx], ...tempTask };
            } else {
                workspaceData.tasks.push(tempTask);
            }
            renderWorkspacePhaseTasks('qualification');
            renderWorkspacePhaseTasks('capture');
            renderWorkspacePhaseTasks('bid_decision');
        }

        await saveData('task', taskData);
        // Refresh with real server data (replaces temp IDs with real ones)
        if (currentWorkspaceOppId) {
            refreshWorkspaceTasks();
        }
    });

    // Search functionality
    function setupSearch() {
        // Agency search - uses card view
        document.getElementById('agencySearch')?.addEventListener('input', e => {
            filterAgenciesView();
        });
        
        // Contact search - uses card view
        document.getElementById('contactSearch')?.addEventListener('input', e => {
            filterContactsView();
        });
        
        // Opportunity search - special plural handling
        document.getElementById('opportunitySearch')?.addEventListener('input', e => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#opportunitiesTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Proposal search
        document.getElementById('proposalSearch')?.addEventListener('input', e => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#proposalsTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Task search
        document.getElementById('taskSearch')?.addEventListener('input', e => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('#tasksTableBody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    function filterContactsView() {
        const searchTerm = document.getElementById('contactSearch')?.value.toLowerCase() || '';
        const cards = document.querySelectorAll('.contact-agency-card');
        
        cards.forEach(card => {
            const agencyName = card.querySelector('.contact-agency-title')?.textContent.toLowerCase() || '';
            const contactRows = card.querySelectorAll('.contact-row:not(.contact-header-row)');
            let hasVisibleContacts = false;
            
            contactRows.forEach(row => {
                const contactName = row.querySelector('.contact-name')?.textContent.toLowerCase() || '';
                const contactEmail = row.querySelector('.contact-email')?.textContent.toLowerCase() || '';
                const contactDivision = row.querySelector('.contact-division')?.textContent.toLowerCase() || '';
                const contactRole = row.querySelector('.contact-role')?.textContent.toLowerCase() || '';
                
                const matches = contactName.includes(searchTerm) || 
                               contactEmail.includes(searchTerm) || 
                               contactDivision.includes(searchTerm) ||
                               contactRole.includes(searchTerm) ||
                               agencyName.includes(searchTerm);
                
                row.style.display = matches || searchTerm === '' ? '' : 'none';
                if (matches || searchTerm === '') hasVisibleContacts = true;
            });
            
            // Hide agency card if no contacts match
            card.style.display = hasVisibleContacts || searchTerm === '' ? '' : 'none';
        });
    }

    // ==================== RELATED ITEM SEARCH FUNCTIONS ====================
    
    // Update related item options based on "Related To" selection
    function updateRelatedItemOptions(preserveSelection = false) {
        return new Promise((resolve) => {
            const relatedTo = document.getElementById('taskRelatedTo').value;
            const group = document.getElementById('relatedItemGroup');
            const label = document.getElementById('relatedItemLabel');
            const searchInput = document.getElementById('relatedItemSearch');
            const dropdown = document.getElementById('relatedItemDropdown');
            const selectedDisplay = document.getElementById('selectedRelatedItem');
            
            // Clear previous selection only if not preserving
            if (!preserveSelection) {
                document.getElementById('taskRelatedItemId').value = '';
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.style.display = 'block';
                }
                if (selectedDisplay) selectedDisplay.style.display = 'none';
            }
            if (dropdown) dropdown.classList.remove('show');
            
            if (!relatedTo) {
                group.style.display = 'none';
                resolve();
                return;
            }
            
            // Show the related item group
            group.style.display = 'block';
            label.textContent = relatedTo;
            
            // Populate dropdown with items
            populateRelatedItemDropdown(relatedTo, '');
            resolve();
        });
    }
    
    // Get items list based on type (case-insensitive)
    function getItemsForType(type) {
        const normalizedType = (type || '').charAt(0).toUpperCase() + (type || '').slice(1).toLowerCase();
        switch(normalizedType) {
            case 'Agency':
                return agencies.map(a => ({
                    id: a.id,
                    title: a.name,
                    subtitle: `${a.type || ''} • ${a.location || ''}`
                }));
            case 'Contact':
                // Combine Federal and Company contacts with type indicators
                const federalContacts = contacts.map(c => ({
                    id: c.id,
                    contactType: 'federal',
                    title: `${c.firstName} ${c.lastName}`,
                    subtitle: `${c.title || ''} • ${c.agencyName || 'No Agency'}`,
                    sortName: `${c.firstName} ${c.lastName}`.toLowerCase()
                }));
                const commercialContacts = companyContacts.map(c => ({
                    id: c.id,
                    contactType: 'commercial',
                    title: `${c.first_name} ${c.last_name}`,
                    subtitle: `${c.title || ''} • ${c.companyName || 'No Company'}`,
                    sortName: `${c.first_name} ${c.last_name}`.toLowerCase()
                }));
                // Combine and sort alphabetically by name
                return [...federalContacts, ...commercialContacts].sort((a, b) => a.sortName.localeCompare(b.sortName));
            case 'Opportunity':
                return opportunities.map(o => ({
                    id: o.id,
                    title: o.title,
                    subtitle: `${o.agencyName || ''} • $${parseFloat(o.value || 0).toLocaleString()}`
                }));
            case 'Proposal':
                return proposals.map(p => ({
                    id: p.id,
                    title: p.title,
                    subtitle: `${p.agencyName || ''} • $${parseFloat(p.value || 0).toLocaleString()}`
                }));
            case 'Event':
                return (events || []).map(e => ({
                    id: e.id,
                    title: e.name,
                    subtitle: `${e.event_type || ''} • ${formatEventDateTime(e.start_datetime)}`
                }));
            default:
                return [];
        }
    }
    
    // Populate the related item dropdown
    function populateRelatedItemDropdown(type, searchTerm) {
        const dropdown = document.getElementById('relatedItemDropdown');
        if (!dropdown) return;
        
        const items = getItemsForType(type);
        const filtered = items.filter(item => 
            item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.subtitle.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        if (filtered.length === 0) {
            dropdown.innerHTML = '<div class="search-select-no-results">No results found</div>';
        } else {
            dropdown.innerHTML = filtered.map(item => {
                const contactType = item.contactType || '';
                const badge = contactType ? `<span class="contact-type-badge ${contactType}">${contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>` : '';
                return `
                <div class="search-select-item" onclick="selectRelatedItem('${type}', ${item.id}, '${item.title.replace(/'/g, "\\'")}', '${item.subtitle.replace(/'/g, "\\'")}', '${contactType}')">
                    <div class="item-title">${item.title}${badge}</div>
                    <div class="item-subtitle">${item.subtitle}</div>
                </div>
            `}).join('');
        }
    }
    
    // Filter related items based on search input
    function filterRelatedItems() {
        const relatedTo = document.getElementById('taskRelatedTo').value;
        const searchTerm = document.getElementById('relatedItemSearch').value;
        populateRelatedItemDropdown(relatedTo, searchTerm);
    }
    
    // Show the related item dropdown
    function showRelatedItemDropdown() {
        const dropdown = document.getElementById('relatedItemDropdown');
        if (dropdown) dropdown.classList.add('show');
    }
    
    // Hide the related item dropdown
    function hideRelatedItemDropdown() {
        setTimeout(() => {
            const dropdown = document.getElementById('relatedItemDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }, 200);
    }
    
    // Select a related item
    function selectRelatedItem(type, id, title, subtitle, contactType = '') {
        document.getElementById('taskRelatedItemId').value = id;
        document.getElementById('relatedItemSearch').value = '';
        
        // Store contact type if this is a contact selection
        if (type === 'Contact' && contactType) {
            document.getElementById('taskRelatedContactType').value = contactType;
        } else {
            document.getElementById('taskRelatedContactType').value = '';
        }
        
        const badge = contactType ? `<span class="contact-type-badge ${contactType}">${contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>` : '';
        const selectedDisplay = document.getElementById('selectedRelatedItem');
        selectedDisplay.innerHTML = `
            <div class="item-info">
                <div class="item-name" style="display: flex; align-items: center; gap: 8px;">${title}${badge}</div>
                <div class="item-detail">${subtitle}</div>
            </div>
            <button type="button" class="remove-btn" onclick="clearRelatedItem()">×</button>
        `;
        selectedDisplay.style.display = 'flex';
        
        // Hide dropdown
        document.getElementById('relatedItemDropdown').classList.remove('show');
    }
    
    // Select related item by ID (for editing)
    function selectRelatedItemById(type, id, contactType = '') {
        const items = getItemsForType(type);
        // For contacts, we need to match both ID and contactType since federal and commercial can have same IDs
        let item;
        if (type === 'Contact' && contactType) {
            item = items.find(i => i.id == id && i.contactType === contactType);
        } else {
            item = items.find(i => i.id == id);
        }
        if (item) {
            selectRelatedItem(type, item.id, item.title, item.subtitle, item.contactType || '');
        }
    }
    
    // Clear selected related item
    function clearRelatedItem() {
        document.getElementById('taskRelatedItemId').value = '';
        document.getElementById('taskRelatedContactType').value = '';
        document.getElementById('selectedRelatedItem').style.display = 'none';
        document.getElementById('relatedItemSearch').value = '';
        document.getElementById('relatedItemSearch').style.display = 'block';
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-select-container')) {
            // Close all search-select dropdowns
            document.querySelectorAll('.search-select-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Charts
    let agencyChart, pipelineChart, proposalAgencyChart, proposalPipelineChart, reportAgencyChart, reportPipelineChart;

    function setupCharts() {
        // Dashboard charts - Opportunities by Agency (ALL agencies with opportunities, sorted by count)
        const agencyCtx = document.getElementById('agencyChart')?.getContext('2d');
        if (agencyCtx) {
            // Build agency data for opportunities - include all agencies with at least 1 opportunity
            const agencyOppCounts = agencies.map(a => ({
                name: a.name,
                shortName: a.name.length > 20 ? a.name.substring(0, 20) + '...' : a.name,
                count: opportunities.filter(o => o.agency_id == a.id).length
            })).filter(a => a.count > 0).sort((a, b) => b.count - a.count);
            
            if (agencyChart) agencyChart.destroy();
            agencyChart = new Chart(agencyCtx, {
                type: 'bar',
                data: {
                    labels: agencyOppCounts.map(d => d.shortName),
                    datasets: [{ 
                        label: 'Opportunities', 
                        data: agencyOppCounts.map(d => d.count), 
                        backgroundColor: 'rgba(102, 126, 234, 0.7)', 
                        borderColor: 'rgba(102, 126, 234, 1)', 
                        borderWidth: 1 
                    }]
                },
                options: { 
                    responsive: true, 
                    indexAxis: agencyOppCounts.length > 8 ? 'y' : 'x',
                    scales: { 
                        x: { beginAtZero: true, ticks: { stepSize: 1 } },
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const idx = context[0].dataIndex;
                                    return agencyOppCounts[idx].name;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Dashboard charts - Proposals by Agency (ALL agencies with proposals, sorted by count)
        const proposalAgencyCtx = document.getElementById('proposalAgencyChart')?.getContext('2d');
        if (proposalAgencyCtx) {
            // Build agency data for proposals - include all agencies with at least 1 proposal
            const agencyPropCounts = agencies.map(a => ({
                name: a.name,
                shortName: a.name.length > 20 ? a.name.substring(0, 20) + '...' : a.name,
                count: proposals.filter(p => p.agency_id == a.id).length
            })).filter(a => a.count > 0).sort((a, b) => b.count - a.count);
            
            if (proposalAgencyChart) proposalAgencyChart.destroy();
            proposalAgencyChart = new Chart(proposalAgencyCtx, {
                type: 'bar',
                data: {
                    labels: agencyPropCounts.map(d => d.shortName),
                    datasets: [{ 
                        label: 'Proposals', 
                        data: agencyPropCounts.map(d => d.count), 
                        backgroundColor: 'rgba(40, 167, 69, 0.7)', 
                        borderColor: 'rgba(40, 167, 69, 1)', 
                        borderWidth: 1 
                    }]
                },
                options: { 
                    responsive: true, 
                    indexAxis: agencyPropCounts.length > 8 ? 'y' : 'x',
                    scales: { 
                        x: { beginAtZero: true, ticks: { stepSize: 1 } },
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const idx = context[0].dataIndex;
                                    return agencyPropCounts[idx].name;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Opportunity Pipeline (doughnut)
        const pipelineCtx = document.getElementById('pipelineChart')?.getContext('2d');
        if (pipelineCtx) {
            const pipelineData = opportunities.reduce((acc, o) => { acc[o.status] = (acc[o.status] || 0) + 1; return acc; }, {});
            if (pipelineChart) pipelineChart.destroy();
            pipelineChart = new Chart(pipelineCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(pipelineData),
                    datasets: [{ data: Object.values(pipelineData), backgroundColor: ['#667eea', '#764ba2', '#f8b400', '#28a745', '#dc3545'] }]
                },
                options: { responsive: true }
            });
        }

        // Proposal Pipeline (doughnut)
        const proposalPipelineCtx = document.getElementById('proposalPipelineChart')?.getContext('2d');
        if (proposalPipelineCtx) {
            const propPipelineData = proposals.reduce((acc, p) => { acc[p.status] = (acc[p.status] || 0) + 1; return acc; }, {});
            if (proposalPipelineChart) proposalPipelineChart.destroy();
            proposalPipelineChart = new Chart(proposalPipelineCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(propPipelineData),
                    datasets: [{ data: Object.values(propPipelineData), backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#20c997', '#dc3545'] }]
                },
                options: { responsive: true }
            });
        }

        // Report charts (same logic as dashboard charts)
        const reportAgencyCtx = document.getElementById('reportAgencyChart')?.getContext('2d');
        if (reportAgencyCtx) {
            const agencyOppCounts = agencies.map(a => ({
                name: a.name,
                shortName: a.name.length > 20 ? a.name.substring(0, 20) + '...' : a.name,
                count: opportunities.filter(o => o.agency_id == a.id).length
            })).filter(a => a.count > 0).sort((a, b) => b.count - a.count);
            
            if (reportAgencyChart) reportAgencyChart.destroy();
            reportAgencyChart = new Chart(reportAgencyCtx, {
                type: 'bar',
                data: {
                    labels: agencyOppCounts.map(d => d.shortName),
                    datasets: [{ 
                        label: 'Opportunities', 
                        data: agencyOppCounts.map(d => d.count), 
                        backgroundColor: 'rgba(102, 126, 234, 0.7)', 
                        borderColor: 'rgba(102, 126, 234, 1)', 
                        borderWidth: 1 
                    }]
                },
                options: { 
                    responsive: true, 
                    indexAxis: agencyOppCounts.length > 8 ? 'y' : 'x',
                    scales: { 
                        x: { beginAtZero: true, ticks: { stepSize: 1 } },
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const idx = context[0].dataIndex;
                                    return agencyOppCounts[idx].name;
                                }
                            }
                        }
                    }
                }
            });
        }

        const reportPipelineCtx = document.getElementById('reportPipelineChart')?.getContext('2d');
        if (reportPipelineCtx) {
            const pipelineData = opportunities.reduce((acc, o) => { acc[o.status] = (acc[o.status] || 0) + 1; return acc; }, {});
            if (reportPipelineChart) reportPipelineChart.destroy();
            reportPipelineChart = new Chart(reportPipelineCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(pipelineData),
                    datasets: [{ data: Object.values(pipelineData), backgroundColor: ['#667eea', '#764ba2', '#f8b400', '#28a745', '#dc3545'] }]
                },
                options: { responsive: true }
            });
        }
    }

    // ==================== CALENDAR FUNCTIONS ====================
    
    // Initialize calendar
    function initCalendar() {
        calendarDate = new Date();
        selectedDate = new Date();
        populateCalendarProposalFilter();
        renderCalendar();
    }
    
    // Populate item filter dropdown for calendar
    function populateCalendarProposalFilter() {
        const select = document.getElementById('calendarProposalFilter');
        if (!select) return;
        
        let html = '<option value="ALL">All Items</option>';
        
        // Add proposals to filter
        if (proposals.length > 0) {
            html += '<optgroup label="📄 Proposals">';
            proposals.forEach(p => {
                const agencyName = p.agencyName || agencies.find(a => a.id == p.agency_id)?.name || '';
                html += `<option value="${p.id}">${p.title}${agencyName ? ' (' + agencyName + ')' : ''}</option>`;
            });
            html += '</optgroup>';
        }
        
        select.innerHTML = html;
    }
    
    // Refresh calendar (called when filter changes)
    function refreshCalendar() {
        renderCalendar();
    }
    
    // Set calendar view mode
    function setCalendarView(view, btn) {
        calendarView = view;
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderCalendar();
    }
    
    // Navigate calendar
    function navigateCalendar(direction) {
        if (direction === 'today') {
            calendarDate = new Date();
            selectedDate = new Date();
        } else if (direction === 'prev') {
            if (calendarView === 'month') {
                calendarDate.setMonth(calendarDate.getMonth() - 1);
            } else if (calendarView === 'week') {
                calendarDate.setDate(calendarDate.getDate() - 7);
            } else {
                calendarDate.setDate(calendarDate.getDate() - 1);
                selectedDate = new Date(calendarDate);
            }
        } else if (direction === 'next') {
            if (calendarView === 'month') {
                calendarDate.setMonth(calendarDate.getMonth() + 1);
            } else if (calendarView === 'week') {
                calendarDate.setDate(calendarDate.getDate() + 7);
            } else {
                calendarDate.setDate(calendarDate.getDate() + 1);
                selectedDate = new Date(calendarDate);
            }
        }
        renderCalendar();
    }
    
    // Get filtered tasks for calendar (filter by proposal)
    function getCalendarEvents() {
        const typeFilter = document.getElementById('calendarTypeFilter')?.value || 'ALL';
        const itemFilter = document.getElementById('calendarProposalFilter')?.value || 'ALL';
        
        let calendarItems = [];
        
        // Add Tasks
        if (typeFilter === 'ALL' || typeFilter === 'task') {
            tasks.filter(t => t.dueDate).forEach(t => {
                // Apply item filter for tasks
                if (itemFilter !== 'ALL') {
                    if (t.relatedTo === 'Proposal' && t.related_item_id == itemFilter) {
                        calendarItems.push({ ...t, eventType: 'task', eventDate: t.dueDate });
                    }
                } else {
                    calendarItems.push({ ...t, eventType: 'task', eventDate: t.dueDate });
                }
            });
        }
        
        // Add Proposals (both Submit Date and Due Date as separate events)
        if (typeFilter === 'ALL' || typeFilter === 'proposal') {
            proposals.forEach(p => {
                // Apply item filter for proposals
                if (itemFilter === 'ALL' || itemFilter == p.id) {
                    // Submit Date (green)
                    if (p.submitDate) {
                        calendarItems.push({
                            id: p.id,
                            title: p.title + ' (Submit)',
                            eventType: 'proposal-submit',
                            eventDate: p.submitDate,
                            status: p.status,
                            priority: 'Medium',
                            value: p.value,
                            agencyName: p.agencyName,
                            winProbability: p.winProbability,
                            description: p.description,
                            submitDate: p.submitDate,
                            dueDate: p.dueDate
                        });
                    }
                    // Due Date (red)
                    if (p.dueDate) {
                        calendarItems.push({
                            id: p.id,
                            title: p.title + ' (Due)',
                            eventType: 'proposal-due',
                            eventDate: p.dueDate,
                            status: p.status,
                            priority: 'High', // Due dates are high priority
                            value: p.value,
                            agencyName: p.agencyName,
                            winProbability: p.winProbability,
                            description: p.description,
                            submitDate: p.submitDate,
                            dueDate: p.dueDate
                        });
                    }
                }
            });
        }
        
        // Add Opportunities (using dueDate)
        if (typeFilter === 'ALL' || typeFilter === 'opportunity') {
            opportunities.filter(o => o.dueDate).forEach(o => {
                // Apply item filter (skip if filtering by specific proposal)
                if (itemFilter === 'ALL') {
                    calendarItems.push({
                        id: o.id,
                        title: o.title,
                        eventType: 'opportunity',
                        eventDate: o.dueDate,
                        status: o.status,
                        priority: o.priority || 'Medium',
                        value: o.value,
                        agencyName: o.agencyName,
                        description: o.description
                    });
                }
            });
        }
        
        // Add CRM Events (using start_datetime)
        if (typeFilter === 'ALL' || typeFilter === 'event') {
            (events || []).filter(e => e.start_datetime).forEach(e => {
                // Apply item filter (skip if filtering by specific proposal)
                if (itemFilter === 'ALL') {
                    calendarItems.push({
                        id: e.id,
                        title: e.name,
                        eventType: 'crm-event',
                        eventDate: e.start_datetime.split(' ')[0], // Just the date part
                        status: e.status,
                        priority: e.priority || 'Medium',
                        event_type: e.event_type,
                        location: e.location,
                        virtual_link: e.virtual_link,
                        description: e.description,
                        start_datetime: e.start_datetime,
                        end_datetime: e.end_datetime
                    });
                }
            });
        }
        
        return calendarItems;
    }
    
    // Get events for a specific date
    function getEventsForDate(date) {
        const dateStr = formatDateString(date);
        return getCalendarEvents().filter(e => e.eventDate === dateStr);
    }
    
    // Legacy function for compatibility
    function getTasksForDate(date) {
        return getEventsForDate(date);
    }
    
    // Format date to YYYY-MM-DD
    function formatDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    // Check if two dates are the same day
    function isSameDay(d1, d2) {
        return d1.getFullYear() === d2.getFullYear() &&
               d1.getMonth() === d2.getMonth() &&
               d1.getDate() === d2.getDate();
    }
    
    // Update period label
    function updatePeriodLabel() {
        const label = document.getElementById('calendarPeriodLabel');
        if (!label) return;
        
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        if (calendarView === 'month') {
            label.textContent = `${months[calendarDate.getMonth()]} ${calendarDate.getFullYear()}`;
        } else if (calendarView === 'week') {
            const weekStart = getWeekStart(calendarDate);
            const weekEnd = new Date(weekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            label.textContent = `${months[weekStart.getMonth()]} ${weekStart.getDate()} - ${months[weekEnd.getMonth()]} ${weekEnd.getDate()}, ${weekEnd.getFullYear()}`;
        } else {
            const viewDate = selectedDate || calendarDate;
            label.textContent = `${days[viewDate.getDay()]}, ${months[viewDate.getMonth()]} ${viewDate.getDate()}, ${viewDate.getFullYear()}`;
        }
    }
    
    // Get start of week (Sunday)
    function getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        d.setDate(d.getDate() - day);
        return d;
    }
    
    // Render calendar based on current view
    function renderCalendar() {
        updatePeriodLabel();
        const container = document.getElementById('calendarContainer');
        if (!container) return;
        
        if (calendarView === 'month') {
            renderMonthView(container);
        } else if (calendarView === 'week') {
            renderWeekView(container);
        } else {
            renderDayView(container);
        }
    }
    
    // Render Month View
    function renderMonthView(container) {
        const year = calendarDate.getFullYear();
        const month = calendarDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const today = new Date();
        
        let html = '<div class="month-grid">';
        
        // Header row
        days.forEach(day => {
            html += `<div class="month-header">${day}</div>`;
        });
        
        // Day cells
        let currentDate = new Date(startDate);
        for (let i = 0; i < 42; i++) {
            const isOtherMonth = currentDate.getMonth() !== month;
            const isToday = isSameDay(currentDate, today);
            const isSelected = selectedDate && isSameDay(currentDate, selectedDate);
            const dayEvents = getEventsForDate(currentDate);
            
            let classes = 'month-day';
            if (isOtherMonth) classes += ' other-month';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            
            html += `<div class="${classes}" onclick="selectDate(new Date(${currentDate.getTime()}))">`;
            html += `<div class="day-number">${currentDate.getDate()}</div>`;
            html += '<div class="day-tasks">';
            
            const maxVisible = 3;
            dayEvents.slice(0, maxVisible).forEach(event => {
                const eventTypeClass = `event-${event.eventType}`;
                const tooltip = getEventTooltip(event).replace(/"/g, '&quot;');
                const clickHandler = getEventClickHandler(event);
                const dblClickHandler = getEventDblClickHandler(event);
                html += `<div class="day-task ${eventTypeClass}" onclick="event.stopPropagation(); ${clickHandler}" ondblclick="event.stopPropagation(); ${dblClickHandler}" title="${tooltip}">${event.title}</div>`;
            });
            
            if (dayEvents.length > maxVisible) {
                html += `<div class="more-tasks" onclick="event.stopPropagation(); selectDate(new Date(${currentDate.getTime()}))">+${dayEvents.length - maxVisible} more</div>`;
            }
            
            html += '</div></div>';
            
            currentDate.setDate(currentDate.getDate() + 1);
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // Render Week View
    function renderWeekView(container) {
        const weekStart = getWeekStart(calendarDate);
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const today = new Date();
        
        let headerHtml = '<div class="week-grid">';
        let contentHtml = '<div class="week-grid">';
        
        for (let i = 0; i < 7; i++) {
            const currentDate = new Date(weekStart);
            currentDate.setDate(weekStart.getDate() + i);
            const isToday = isSameDay(currentDate, today);
            const dayEvents = getEventsForDate(currentDate);
            
            // Header
            headerHtml += `<div class="week-day-header">
                <div class="day-name">${days[i]}</div>
                <div class="day-date">${months[currentDate.getMonth()]} ${currentDate.getDate()}</div>
            </div>`;
            
            // Content
            contentHtml += `<div class="week-day-content ${isToday ? 'today' : ''}" onclick="selectDate(new Date(${currentDate.getTime()}))">`;
            
            dayEvents.forEach(event => {
                const eventTypeClass = `event-${event.eventType}`;
                const tooltip = getEventTooltip(event).replace(/"/g, '&quot;');
                const clickHandler = getEventClickHandler(event);
                const dblClickHandler = getEventDblClickHandler(event);
                const metaInfo = getEventMetaInfo(event);
                contentHtml += `<div class="week-task ${eventTypeClass}" onclick="event.stopPropagation(); ${clickHandler}" ondblclick="event.stopPropagation(); ${dblClickHandler}" title="${tooltip}">
                    <div class="week-task-title">${event.title} <span class="event-type-badge ${event.eventType}">${event.eventType}</span></div>
                    <div class="week-task-meta">${metaInfo}</div>
                </div>`;
            });
            
            if (dayEvents.length === 0) {
                contentHtml += '<div style="color: #aaa; font-size: 0.8rem; text-align: center; padding: 20px;">No events</div>';
            }
            
            contentHtml += '</div>';
        }
        
        headerHtml += '</div>';
        contentHtml += '</div>';
        
        container.innerHTML = headerHtml + contentHtml;
    }
    
    // Render Day View
    function renderDayView(container) {
        const viewDate = selectedDate || calendarDate;
        const dayEvents = getEventsForDate(viewDate);
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Sort events by priority (High first)
        const priorityOrder = { 'High': 1, 'Medium': 2, 'Low': 3 };
        dayEvents.sort((a, b) => (priorityOrder[a.priority] || 3) - (priorityOrder[b.priority] || 3));
        
        let html = '<div class="day-view">';
        html += `<div class="day-view-header">
            <h3>${days[viewDate.getDay()]}, ${months[viewDate.getMonth()]} ${viewDate.getDate()}, ${viewDate.getFullYear()}</h3>
            <p>${dayEvents.length} event${dayEvents.length !== 1 ? 's' : ''} scheduled</p>
        </div>`;
        
        if (dayEvents.length === 0) {
            html += `<div class="no-tasks-message">
                <h4>📭 No Events</h4>
                <p>No tasks, proposals, or opportunities scheduled for this day.</p>
            </div>`;
        } else {
            html += '<div class="day-task-list">';
            dayEvents.forEach(event => {
                const eventTypeClass = `event-${event.eventType}`;
                const tooltip = getEventTooltip(event).replace(/"/g, '&quot;');
                const clickHandler = getEventClickHandler(event);
                const dblClickHandler = getEventDblClickHandler(event);
                const detailInfo = getEventDetailInfo(event);
                const badgeInfo = getEventBadgeInfo(event);
                
                html += `<div class="day-task-item ${eventTypeClass}" onclick="${clickHandler}" ondblclick="${dblClickHandler}" title="${tooltip}">
                    <div class="day-task-time"><span class="event-type-badge ${event.eventType}">${event.eventType}</span></div>
                    <div class="day-task-details">
                        <h4>${event.title}</h4>
                        <p>${detailInfo}</p>
                        <div class="day-task-badges">
                            ${badgeInfo}
                        </div>
                    </div>
                </div>`;
            });
            html += '</div>';
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // Select a date (drill into day view)
    function selectDate(date) {
        selectedDate = date;
        calendarDate = new Date(date);
        setCalendarView('day', document.querySelector('.view-btn[data-view="day"]'));
    }
    
    // Show task detail panel
    function showTaskDetail(taskId) {
        const task = tasks.find(t => t.id == taskId);
        if (!task) return;
        
        calendarTaskId = taskId;
        
        document.getElementById('taskPanelTitle').textContent = task.title;
        document.getElementById('taskPanelDate').textContent = task.dueDate ? `Due: ${new Date(task.dueDate + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}` : '';
        
        const assignedName = task.assignedToDisplayName || task.assignedToUsername || users.find(u => u.id == task.assigned_to_user_id)?.display_name || users.find(u => u.id == task.assigned_to_user_id)?.username || task.assignedTo || 'Unassigned';
        const relatedItemName = getRelatedItemName(task.relatedTo, task.related_item_id, task.related_contact_type);
        const relatedDisplay = task.relatedTo ? `${task.relatedTo}${relatedItemName ? ': <strong>' + relatedItemName + '</strong>' : ''}` : '—';
        
        let bodyHtml = `
            <div class="task-panel-row">
                <div class="task-panel-label">Status</div>
                <div class="task-panel-value"><span class="status-badge status-${(task.status || '').toLowerCase().replace(' ', '')}">${task.status}</span></div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Priority</div>
                <div class="task-panel-value"><span class="status-badge priority-${(task.priority || 'low').toLowerCase()}" style="background: ${task.priority === 'High' ? 'rgba(220,53,69,0.1)' : task.priority === 'Medium' ? 'rgba(255,193,7,0.1)' : 'rgba(40,167,69,0.1)'}; color: ${task.priority === 'High' ? '#dc3545' : task.priority === 'Medium' ? '#856404' : '#28a745'}">${task.priority || 'Low'}</span></div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Related To</div>
                <div class="task-panel-value">${relatedDisplay}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Assigned To</div>
                <div class="task-panel-value">${assignedName}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Due Date</div>
                <div class="task-panel-value">${task.dueDate || '—'}</div>
            </div>
            <div class="task-panel-row">
                <div class="task-panel-label">Description</div>
                <div class="task-panel-value">${task.description || 'No description provided.'}</div>
            </div>
        `;
        
        document.getElementById('taskPanelBody').innerHTML = bodyHtml;
        document.getElementById('taskDetailPanel').classList.add('open');
        document.getElementById('taskPanelOverlay').classList.add('open');
    }
    
    // Close task detail panel
    function closeTaskPanel() {
        document.getElementById('taskDetailPanel').classList.remove('open');
        document.getElementById('taskPanelOverlay').classList.remove('open');
        calendarTaskId = null;
    }
    
    // Edit task from panel
    function editTaskFromPanel() {
        if (calendarTaskId) {
            const taskIdToEdit = calendarTaskId; // Save ID before closing panel
            closeTaskPanel();
            editItem('task', taskIdToEdit);
        }
    }
    
    // ==================== SPICY KANBAN FUNCTIONS ====================
    
    const kanbanStatuses = ['To Do', 'In Progress', 'Done'];
    let draggedTaskId = null;
    
    // Date navigation state
    let kanbanCurrentDate = new Date();
    kanbanCurrentDate.setHours(0, 0, 0, 0);
    
    // Populate the user filter dropdown
    function populateKanbanUserFilter() {
        const userFilter = document.getElementById('kanbanUserFilter');
        if (!userFilter) return;
        
        let options = '<option value="all">👥 All Users</option>';
        users.forEach(u => {
            const displayName = u.display_name || u.username;
            options += `<option value="${u.id}">${displayName}</option>`;
        });
        userFilter.innerHTML = options;
    }
    
    // Update date range visibility and reset date
    function updateKanbanDateRange() {
        const dateRange = document.getElementById('kanbanDateRange').value;
        const dateNav = document.getElementById('kanbanDateNav');
        
        if (dateRange === 'all') {
            dateNav.style.display = 'none';
        } else {
            dateNav.style.display = 'flex';
            kanbanCurrentDate = new Date();
            kanbanCurrentDate.setHours(0, 0, 0, 0);
            updateKanbanDateLabel();
        }
        renderKanbanBoard();
    }
    
    // Navigate date forward or backward
    function navigateKanbanDate(direction) {
        const dateRange = document.getElementById('kanbanDateRange').value;
        
        if (dateRange === 'day') {
            kanbanCurrentDate.setDate(kanbanCurrentDate.getDate() + direction);
        } else if (dateRange === 'week') {
            kanbanCurrentDate.setDate(kanbanCurrentDate.getDate() + (direction * 7));
        } else if (dateRange === 'month') {
            kanbanCurrentDate.setMonth(kanbanCurrentDate.getMonth() + direction);
        }
        
        updateKanbanDateLabel();
        renderKanbanBoard();
    }
    
    // Navigate to today
    function navigateKanbanToday() {
        kanbanCurrentDate = new Date();
        kanbanCurrentDate.setHours(0, 0, 0, 0);
        updateKanbanDateLabel();
        renderKanbanBoard();
    }
    
    // Update the date label based on current date and range
    function updateKanbanDateLabel() {
        const dateRange = document.getElementById('kanbanDateRange').value;
        const label = document.getElementById('kanbanDateLabel');
        
        if (dateRange === 'day') {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (kanbanCurrentDate.getTime() === today.getTime()) {
                label.textContent = 'Today';
            } else {
                label.textContent = kanbanCurrentDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
            }
        } else if (dateRange === 'week') {
            // Get start and end of week (Sunday to Saturday)
            const startOfWeek = new Date(kanbanCurrentDate);
            startOfWeek.setDate(kanbanCurrentDate.getDate() - kanbanCurrentDate.getDay());
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            
            const startStr = startOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const endStr = endOfWeek.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            label.textContent = `${startStr} - ${endStr}`;
        } else if (dateRange === 'month') {
            label.textContent = kanbanCurrentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
    }
    
    // Check if a task's due date falls within the selected date range
    function isTaskInDateRange(task) {
        const dateRange = document.getElementById('kanbanDateRange')?.value || 'all';
        
        if (dateRange === 'all') return true;
        if (!task.dueDate) return false; // Tasks without due dates are hidden when date filter is active
        
        const taskDate = new Date(task.dueDate + 'T00:00:00');
        taskDate.setHours(0, 0, 0, 0);
        
        if (dateRange === 'day') {
            return taskDate.getTime() === kanbanCurrentDate.getTime();
        } else if (dateRange === 'week') {
            const startOfWeek = new Date(kanbanCurrentDate);
            startOfWeek.setDate(kanbanCurrentDate.getDate() - kanbanCurrentDate.getDay());
            startOfWeek.setHours(0, 0, 0, 0);
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            endOfWeek.setHours(23, 59, 59, 999);
            
            return taskDate >= startOfWeek && taskDate <= endOfWeek;
        } else if (dateRange === 'month') {
            return taskDate.getMonth() === kanbanCurrentDate.getMonth() && 
                   taskDate.getFullYear() === kanbanCurrentDate.getFullYear();
        }
        
        return true;
    }
    
    // Update the item filter dropdown based on view type
    function updateKanbanItemFilter() {
        const viewType = document.getElementById('kanbanViewType').value;
        const itemFilter = document.getElementById('kanbanItemFilter');
        const userId = document.getElementById('kanbanUserFilter')?.value || 'all';
        
        if (viewType === 'all') {
            itemFilter.style.display = 'none';
            renderKanbanBoard();
            return;
        }
        
        itemFilter.style.display = 'block';
        // Handle plural forms correctly
        const pluralMap = { 'proposal': 'Proposals', 'contact': 'Contacts', 'opportunity': 'Opportunities' };
        const pluralName = pluralMap[viewType] || (viewType.charAt(0).toUpperCase() + viewType.slice(1) + 's');
        let options = `<option value="all">All ${pluralName}</option>`;
        
        if (viewType === 'proposal') {
            // Filter proposals by owner if user is selected
            let filteredProposals = proposals;
            if (userId !== 'all') {
                filteredProposals = proposals.filter(p => p.owner_user_id == userId);
            }
            filteredProposals.forEach(p => {
                options += `<option value="${p.id}">${p.title}</option>`;
            });
        } else if (viewType === 'contact') {
            // Filter contacts by owner if user is selected
            let filteredContacts = contacts;
            if (userId !== 'all') {
                filteredContacts = contacts.filter(c => c.owner_user_id == userId);
            }
            filteredContacts.forEach(c => {
                options += `<option value="${c.id}">${c.firstName} ${c.lastName}</option>`;
            });
        } else if (viewType === 'opportunity') {
            // Filter opportunities by owner if user is selected
            let filteredOpportunities = opportunities;
            if (userId !== 'all') {
                filteredOpportunities = opportunities.filter(o => o.owner_user_id == userId);
            }
            filteredOpportunities.forEach(o => {
                options += `<option value="${o.id}">${o.title}</option>`;
            });
        }
        
        itemFilter.innerHTML = options;
        renderKanbanBoard();
    }
    
    // Get filtered tasks for Kanban
    function getKanbanTasks() {
        const viewType = document.getElementById('kanbanViewType')?.value || 'all';
        const itemId = document.getElementById('kanbanItemFilter')?.value || 'all';
        const userId = document.getElementById('kanbanUserFilter')?.value || 'all';
        
        let filtered = [...tasks];
        
        // Filter by date range first
        filtered = filtered.filter(t => isTaskInDateRange(t));
        
        // Filter by view type (proposal, contact, opportunity)
        if (viewType !== 'all') {
            const relatedType = viewType.charAt(0).toUpperCase() + viewType.slice(1);
            filtered = filtered.filter(t => t.relatedTo === relatedType || t.relatedTo === relatedType.toLowerCase());
            
            if (itemId !== 'all') {
                // Filter by specific item
                filtered = filtered.filter(t => t.related_item_id == itemId);
            }
        }
        
        // Always filter by assigned user if selected
        if (userId !== 'all') {
            filtered = filtered.filter(t => t.assigned_to_user_id == userId);
        }
        
        return filtered;
    }
    
    // Render the Kanban board
    function renderKanbanBoard() {
        const kanbanTasks = getKanbanTasks();
        const dateRange = document.getElementById('kanbanDateRange')?.value || 'all';
        const userId = document.getElementById('kanbanUserFilter')?.value || 'all';
        
        // Build empty state message based on filters
        let emptyMessage = 'No tasks';
        if (dateRange !== 'all' || userId !== 'all') {
            const parts = [];
            if (userId !== 'all') {
                const selectedUser = users.find(u => u.id == userId);
                parts.push(`for ${selectedUser?.display_name || 'selected user'}`);
            }
            if (dateRange !== 'all') {
                parts.push(`in this ${dateRange}`);
            }
            emptyMessage = `No tasks ${parts.join(' ')}`;
        }
        
        // Clear all columns
        document.querySelectorAll('.kanban-column-body').forEach(col => {
            col.innerHTML = '';
        });
        
        // Group tasks by status
        const tasksByStatus = {};
        kanbanStatuses.forEach(status => {
            tasksByStatus[status] = [];
        });
        
        kanbanTasks.forEach(task => {
            let status = task.status || 'To Do';
            // Map old "Completed" to "Done" for backwards compatibility
            if (status === 'Completed') status = 'Done';
            // Map old "Pending" to "To Do" 
            if (status === 'Pending') status = 'To Do';
            // Map old "Review" to "In Progress"
            if (status === 'Review') status = 'In Progress';
            // If status doesn't exist in our columns, put it in To Do
            if (!tasksByStatus[status]) status = 'To Do';
            tasksByStatus[status].push(task);
        });
        
        // Render cards in each column
        kanbanStatuses.forEach(status => {
            const column = document.querySelector(`.kanban-column[data-status="${status}"] .kanban-column-body`);
            const countEl = document.getElementById('count' + status.replace(' ', ''));
            
            if (countEl) countEl.textContent = tasksByStatus[status].length;
            
            if (!column) return;
            
            if (tasksByStatus[status].length === 0) {
                column.innerHTML = `<div class="kanban-empty">${emptyMessage}</div>`;
            } else {
                tasksByStatus[status].forEach(task => {
                    column.innerHTML += createKanbanCard(task);
                });
            }
            
            // Add "Add Task" button at the bottom of each column
            column.innerHTML += `<button class="kanban-add-btn" onclick="openKanbanTaskModal('${status}')">+ Add Task</button>`;
        });
    }
    
    // Open task modal from Kanban with pre-filled status and assigned user
    function openKanbanTaskModal(status) {
        currentEditId = null;
        
        // Reset the form
        const form = document.getElementById('taskForm');
        if (form) form.reset();
        
        // Reset related item fields
        document.getElementById('taskRelatedItemId').value = '';
        document.getElementById('relatedItemGroup').style.display = 'none';
        document.getElementById('selectedRelatedItem').style.display = 'none';
        document.getElementById('selectedRelatedItem').innerHTML = '';
        document.getElementById('relatedItemSearch').value = '';
        
        // Pre-fill status based on column clicked
        document.getElementById('taskStatus').value = status;
        
        // Pre-fill assigned user - use kanban user filter if set, otherwise current user
        const kanbanUserId = document.getElementById('kanbanUserFilter')?.value || 'all';
        const assignedToSelect = document.getElementById('taskAssignedTo');
        if (assignedToSelect) {
            if (kanbanUserId !== 'all') {
                assignedToSelect.value = kanbanUserId;
            } else if (currentUserId) {
                assignedToSelect.value = currentUserId;
            }
        }
        
        // Pre-fill due date if date filter is active
        const dateRange = document.getElementById('kanbanDateRange')?.value || 'all';
        if (dateRange !== 'all') {
            const dueDateInput = document.getElementById('taskDueDate');
            if (dueDateInput) {
                // Format date as YYYY-MM-DD for the date input
                const year = kanbanCurrentDate.getFullYear();
                const month = String(kanbanCurrentDate.getMonth() + 1).padStart(2, '0');
                const day = String(kanbanCurrentDate.getDate()).padStart(2, '0');
                dueDateInput.value = `${year}-${month}-${day}`;
            }
        }
        
        // Set default priority
        document.getElementById('taskPriority').value = 'Medium';
        
        // Check if viewing a filtered view (proposal, contact, opportunity)
        const viewType = document.getElementById('kanbanViewType')?.value || 'all';
        const itemId = document.getElementById('kanbanItemFilter')?.value || 'all';
        
        if (viewType !== 'all' && itemId !== 'all') {
            // Pre-fill Related To based on current filter
            const relatedType = viewType.charAt(0).toUpperCase() + viewType.slice(1); // 'proposal' -> 'Proposal'
            document.getElementById('taskRelatedTo').value = relatedType;
            
            // Trigger the related item options update and then select the item
            updateRelatedItemOptions().then(() => {
                // Set the related item ID
                document.getElementById('taskRelatedItemId').value = itemId;
                
                // Find the item name to display
                let itemName = '';
                if (viewType === 'proposal') {
                    const proposal = proposals.find(p => p.id == itemId);
                    itemName = proposal ? proposal.title : '';
                } else if (viewType === 'contact') {
                    const contact = contacts.find(c => c.id == itemId);
                    itemName = contact ? `${contact.firstName} ${contact.lastName}` : '';
                } else if (viewType === 'opportunity') {
                    const opp = opportunities.find(o => o.id == itemId);
                    itemName = opp ? opp.title : '';
                }
                
                // Show the selected item display
                if (itemName) {
                    const selectedDisplay = document.getElementById('selectedRelatedItem');
                    selectedDisplay.innerHTML = `
                        <div class="item-info">
                            <div class="item-name">${itemName}</div>
                            <div class="item-detail">${relatedType}</div>
                        </div>
                        <button type="button" class="remove-btn" onclick="clearRelatedItem()">×</button>
                    `;
                    selectedDisplay.style.display = 'flex';
                    document.getElementById('relatedItemSearch').style.display = 'none';
                }
            });
        }
        
        // Update modal title
        document.getElementById('taskModalTitle').textContent = 'Add Task';
        
        // Open the modal
        document.getElementById('taskModal').style.display = 'block';
    }
    
    // Create a Kanban card HTML
    function createKanbanCard(task) {
        const priorityClass = `priority-${(task.priority || 'low').toLowerCase()}`;
        const assignedName = task.assignedToDisplayName || task.assignedToUsername || users.find(u => u.id == task.assigned_to_user_id)?.display_name || users.find(u => u.id == task.assigned_to_user_id)?.username || task.assignedTo || '';
        const relatedName = getRelatedItemName(task.relatedTo, task.related_item_id, task.related_contact_type);
        const description = task.description ? task.description.substring(0, 80) + (task.description.length > 80 ? '...' : '') : '';
        
        let dueDateBadge = '';
        if (task.dueDate) {
            const dueDate = new Date(task.dueDate + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const isOverdue = dueDate < today;
            const formattedDate = dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            dueDateBadge = `<span class="kanban-card-badge due-date" style="${isOverdue ? 'background: rgba(220,53,69,0.1); color: #dc3545;' : ''}">📅 ${formattedDate}</span>`;
        }
        
        return `
            <div class="kanban-card ${priorityClass}" 
                 draggable="true" 
                 data-task-id="${task.id}"
                 ondragstart="handleDragStart(event, ${task.id})"
                 ondragend="handleDragEnd(event)"
                 onclick="editItem('task', ${task.id})">
                ${task.relatedTo && relatedName ? `<div class="kanban-card-related">${task.relatedTo}: ${relatedName}</div>` : ''}
                <div class="kanban-card-title">${task.title}</div>
                <div class="kanban-card-meta">
                    ${dueDateBadge}
                    <span class="kanban-card-badge ${priorityClass}">${task.priority || 'Medium'}</span>
                    ${assignedName ? `<span class="kanban-card-badge assigned">👤 ${assignedName}</span>` : ''}
                </div>
                ${description ? `<div class="kanban-card-description">${description}</div>` : ''}
            </div>
        `;
    }
    
    // Drag and Drop Handlers
    function handleDragStart(event, taskId) {
        draggedTaskId = taskId;
        event.target.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', taskId);
    }
    
    function handleDragEnd(event) {
        event.target.classList.remove('dragging');
        document.querySelectorAll('.kanban-column-body').forEach(col => {
            col.classList.remove('drag-over');
        });
    }
    
    function handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        event.currentTarget.classList.add('drag-over');
    }
    
    function handleDragLeave(event) {
        event.currentTarget.classList.remove('drag-over');
    }
    
    async function handleDrop(event, newStatus) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');
        
        if (!draggedTaskId) return;
        
        const task = tasks.find(t => t.id == draggedTaskId);
        if (!task) return;
        
        // Update task status
        try {
            const response = await fetch(`${API_URL}?action=saveTask`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: task.id,
                    title: task.title,
                    relatedTo: task.relatedTo,
                    related_item_id: task.related_item_id,
                    dueDate: task.dueDate,
                    priority: task.priority,
                    status: newStatus,
                    assigned_to_user_id: task.assigned_to_user_id,
                    assignedTo: task.assignedTo || task.assignedToUsername || '',
                    description: task.description
                })
            });
            
            if (response.ok) {
                // Update local data
                task.status = newStatus;
                renderKanbanBoard();
                
                // Show brief success indicator
                const card = document.querySelector(`[data-task-id="${draggedTaskId}"]`);
                if (card) {
                    card.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.5)';
                    setTimeout(() => {
                        card.style.boxShadow = '';
                    }, 500);
                }
            } else {
                showToast('Failed to update task status', 'error');
                renderKanbanBoard();
            }
        } catch (error) {
            console.error('Error updating task:', error);
            showToast('Error updating task status', 'error');
            renderKanbanBoard();
        }
        
        draggedTaskId = null;
    }
    
    // =============================================
    // CONTACT NOTES PANEL FUNCTIONS
    // =============================================
    
    function openContactPanel(contactId) {
        currentContactId = contactId;
        const contact = contacts.find(c => c.id == contactId);
        if (!contact) return;
        
        // Set header info
        document.getElementById('contactPanelName').textContent = `${contact.firstName} ${contact.lastName}`;
        document.getElementById('contactPanelTitle').textContent = `${contact.title || ''} - ${contact.agencyName || 'No Agency'}`;
        
        // Render contact info section
        renderContactInfo(contact);
        
        // Populate filter dropdowns
        populateNoteFilters();
        
        // Load notes
        loadContactNotes();
        
        // Switch to info tab by default
        switchContactTab('info');
        
        // Show panel
        document.getElementById('contactPanelOverlay').classList.add('open');
        document.getElementById('contactPanel').classList.add('open');
    }
    
    function closeContactPanel() {
        document.getElementById('contactPanelOverlay').classList.remove('open');
        document.getElementById('contactPanel').classList.remove('open');
        currentContactId = null;
        currentContactNotes = [];
    }
    
    function switchContactTab(tab) {
        // Update tab button styles
        document.querySelectorAll('#contactPanel .contact-panel-tab').forEach(t => {
            t.classList.remove('active');
            if ((tab === 'info' && t.textContent === 'Info') || 
                (tab === 'notes' && t.textContent === 'Notes') ||
                (tab === 'linked' && t.textContent === 'Linked')) {
                t.classList.add('active');
            }
        });
        
        document.getElementById('contactInfoSection').style.display = tab === 'info' ? 'block' : 'none';
        document.getElementById('contactNotesSection').style.display = tab === 'notes' ? 'block' : 'none';
        document.getElementById('contactLinkedSection').style.display = tab === 'linked' ? 'block' : 'none';
        
        if (tab === 'linked') {
            renderContactLinkedItems();
        }
    }
    
    function renderContactLinkedItems() {
        const contactId = currentContactId;
        
        // Render linked opportunities
        const opps = contactOpportunities[contactId] || [];
        const oppContainer = document.getElementById('contactLinkedOpportunities');
        if (opps.length === 0) {
            oppContainer.innerHTML = '<div class="no-linked">No linked opportunities</div>';
        } else {
            oppContainer.innerHTML = opps.map(opp => `
                <div class="linked-item opportunity">
                    <div class="linked-item-info">
                        <div class="linked-item-title">${opp.opportunityTitle || 'Untitled'}</div>
                        <div class="linked-item-meta">
                            <span class="linked-item-status ${(opp.opportunityStatus || '').toLowerCase()}">${opp.opportunityStatus || 'N/A'}</span>
                            ${opp.role ? `<span>Role: ${opp.role}</span>` : ''}
                        </div>
                    </div>
                    <div class="linked-item-actions">
                        <button class="linked-unlink-btn" onclick="unlinkContactOpportunity(${contactId}, ${opp.opportunity_id}, 'federal')">✕ Unlink</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Render linked proposals
        const props = contactProposals[contactId] || [];
        const propContainer = document.getElementById('contactLinkedProposals');
        if (props.length === 0) {
            propContainer.innerHTML = '<div class="no-linked">No linked proposals</div>';
        } else {
            propContainer.innerHTML = props.map(prop => `
                <div class="linked-item proposal">
                    <div class="linked-item-info">
                        <div class="linked-item-title">${prop.proposalTitle || 'Untitled'}</div>
                        <div class="linked-item-meta">
                            <span class="linked-item-status ${(prop.proposalStatus || '').toLowerCase().replace(' ', '-')}">${prop.proposalStatus || 'N/A'}</span>
                            ${prop.role ? `<span>Role: ${prop.role}</span>` : ''}
                        </div>
                    </div>
                    <div class="linked-item-actions">
                        <button class="linked-unlink-btn" onclick="unlinkContactProposal(${contactId}, ${prop.proposal_id}, 'federal')">✕ Unlink</button>
                    </div>
                </div>
            `).join('');
        }
    }
    
    // Open contact panel directly to Notes tab
    function openContactNotes(contactId) {
        currentContactId = contactId;
        const contact = contacts.find(c => c.id == contactId);
        if (!contact) return;
        
        // Set header info
        document.getElementById('contactPanelName').textContent = `${contact.firstName} ${contact.lastName}`;
        document.getElementById('contactPanelTitle').textContent = `${contact.title || ''} - ${contact.agencyName || 'No Agency'}`;
        
        // Render contact info section
        renderContactInfo(contact);
        
        // Populate filter dropdowns
        populateNoteFilters();
        
        // Load notes
        loadContactNotes();
        
        // Switch to NOTES tab directly
        switchContactTab('notes');
        
        // Show panel
        document.getElementById('contactPanelOverlay').classList.add('open');
        document.getElementById('contactPanel').classList.add('open');
    }
    
    function renderContactInfo(contact) {
        const agency = agencies.find(a => a.id == contact.agency_id);
        const owner = users.find(u => u.id == contact.owner_user_id);

        const html = `
            <div class="contact-info-row">
                <div class="contact-info-label">Email</div>
                <div class="contact-info-value"><a href="mailto:${contact.email || ''}" style="color: #667eea;">${contact.email || '-'}</a></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Phone</div>
                <div class="contact-info-value">${contact.phone || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Agency</div>
                <div class="contact-info-value">${contact.agencyName || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Division</div>
                <div class="contact-info-value">${contact.division || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Title</div>
                <div class="contact-info-value">${contact.title || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Owner</div>
                <div class="contact-info-value"><span class="owner-badge">${contact.ownerUsername || '-'}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Status</div>
                <div class="contact-info-value"><span class="status-badge status-${(contact.status || '').toLowerCase()}">${contact.status || '-'}</span></div>
            </div>
            ${contact.notes ? `
            <div class="contact-info-row" style="flex-direction: column;">
                <div class="contact-info-label" style="margin-bottom: 8px;">Notes</div>
                <div class="contact-info-value" style="white-space: pre-wrap;">${contact.notes}</div>
            </div>
            ` : ''}
        `;
        
        document.getElementById('contactInfoSection').innerHTML = html;
    }
    
    function populateNoteFilters() {
        // Populate user filter
        const userSelect = document.getElementById('noteFilterUser');
        userSelect.innerHTML = '<option value="">All Users</option>' +
            users.map(u => `<option value="${u.id}">${u.display_name || u.username}</option>`).join('');

        // Clear other filters
        document.getElementById('noteFilterDateFrom').value = '';
        document.getElementById('noteFilterDateTo').value = '';
        document.getElementById('noteFilterType').value = '';
    }
    
    async function loadContactNotes() {
        if (!currentContactId) return;
        
        try {
            // Build query params with filters
            const params = new URLSearchParams({
                action: 'getContactNotes',
                contact_id: currentContactId
            });
            
            const dateFrom = document.getElementById('noteFilterDateFrom').value;
            const dateTo = document.getElementById('noteFilterDateTo').value;
            const userId = document.getElementById('noteFilterUser').value;
            const interactionType = document.getElementById('noteFilterType').value;

            if (dateFrom) params.append('filter_date_from', dateFrom);
            if (dateTo) params.append('filter_date_to', dateTo);
            if (userId) params.append('filter_user_id', userId);
            if (interactionType) params.append('filter_interaction_type', interactionType);
            
            const response = await fetch(`${API_URL}?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                currentContactNotes = data.notes || [];
                renderContactNotes();
            } else {
                console.error('Failed to load notes:', data.error);
            }
        } catch (error) {
            console.error('Error loading contact notes:', error);
        }
    }
    
    function filterContactNotes() {
        loadContactNotes();
    }
    
    function renderContactNotes() {
        const container = document.getElementById('contactNotesList');
        
        if (currentContactNotes.length === 0) {
            container.innerHTML = `
                <div class="no-notes">
                    <div class="no-notes-icon">📝</div>
                    <p>No notes found</p>
                    <p style="font-size: 0.85rem;">Click "Add Note" to create the first interaction note.</p>
                </div>
            `;
            return;
        }
        
        const html = currentContactNotes.map(note => `
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-meta">
                        <span class="note-user">${note.createdByUsername || 'Unknown'}</span>
                        <span class="note-date">${note.displayDate}</span>
                        ${note.note_date ? `<span class="note-date-badge">${new Date(note.note_date + 'T00:00:00').toLocaleDateString()}</span>` : ''}
                        ${note.interaction_type ? `<span class="note-type">${note.interaction_type}</span>` : ''}
                    </div>
                    ${note.canEdit || note.canDelete ? `
                    <div class="note-actions">
                        ${note.canEdit ? `<button class="note-action-btn edit" onclick="editContactNote(${note.id})" title="Edit">✏️</button>` : ''}
                        ${note.canDelete ? `<button class="note-action-btn delete" onclick="deleteContactNote(${note.id})" title="Delete">🗑️</button>` : ''}
                    </div>
                    ` : ''}
                </div>
                <div class="note-text">${escapeHtml(note.note_text)}</div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function openAddNoteModal() {
        editingNoteId = null;
        document.getElementById('noteModalTitle').textContent = 'Add Note';
        document.getElementById('noteId').value = '';
        document.getElementById('noteContactId').value = currentContactId;
        document.getElementById('noteDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('noteInteractionType').value = '';
        document.getElementById('noteText').value = '';

        document.getElementById('noteModal').style.display = 'block';
    }
    
    function editContactNote(noteId) {
        const note = currentContactNotes.find(n => n.id == noteId);
        if (!note) return;
        
        editingNoteId = noteId;
        document.getElementById('noteModalTitle').textContent = 'Edit Note';
        document.getElementById('noteId').value = noteId;
        document.getElementById('noteContactId').value = currentContactId;
        document.getElementById('noteInteractionType').value = note.interaction_type || '';
        document.getElementById('noteText').value = note.note_text || '';
        document.getElementById('noteDate').value = note.note_date || '';

        document.getElementById('noteModal').style.display = 'block';
    }
    
    async function saveContactNote(event) {
        event.preventDefault();
        
        const noteData = {
            id: document.getElementById('noteId').value || null,
            contact_id: document.getElementById('noteContactId').value,
            note_date: document.getElementById('noteDate').value || null,
            interaction_type: document.getElementById('noteInteractionType').value,
            note_text: document.getElementById('noteText').value
        };
        
        if (!noteData.note_text.trim()) {
            showToast('Please enter a note.', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=saveContactNote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                closeNoteModal();
                loadContactNotes();
            } else {
                showToast('Error saving note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving note:', error);
            showToast('Error saving note', 'error');
        }
    }
    
    async function deleteContactNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteContactNote&id=${noteId}`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadContactNotes();
            } else {
                showToast('Error deleting note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showToast('Error deleting note', 'error');
        }
    }
    
    function closeNoteModal() {
        document.getElementById('noteModal').style.display = 'none';
        editingNoteId = null;
    }
    
    // =============================================
    // DIVISION FUNCTIONS
    // =============================================
    
    async function loadDivisionsForAgency(prefix) {
        const agencySelect = document.getElementById(`${prefix}Agency`);
        const divisionInput = document.getElementById(`${prefix}Division`);
        const divisionDatalist = document.getElementById(`${prefix}DivisionList`);
        
        if (!agencySelect || !divisionInput) return;
        
        const agencyId = agencySelect.value;
        
        // Handle datalist (for contacts) vs select (for opportunities)
        const isDatalist = divisionDatalist !== null;
        
        if (isDatalist) {
            // Clear datalist options
            divisionDatalist.innerHTML = '';
        } else {
            // Clear select options
            divisionInput.innerHTML = '<option value="">-- Select Division --</option>';
        }
        
        if (!agencyId) return Promise.resolve();
        
        // Get divisions from divisions table
        const tableDivisions = divisions.filter(d => d.agency_id == agencyId);
        const tableDivisionNames = new Set(tableDivisions.map(d => d.name));
        
        // Get unique divisions from contacts for this agency
        const contactDivisionNames = new Set();
        contacts.forEach(c => {
            if (c.division && c.agency_id == agencyId) {
                contactDivisionNames.add(c.division.trim());
            }
        });
        
        // Combine and deduplicate
        const allDivisionNames = new Set([...tableDivisionNames, ...contactDivisionNames]);
        
        // Sort alphabetically and add options
        [...allDivisionNames].sort((a, b) => a.localeCompare(b)).forEach(name => {
            if (isDatalist) {
                divisionDatalist.innerHTML += `<option value="${name}">`;
            } else {
                divisionInput.innerHTML += `<option value="${name}">${name}</option>`;
            }
        });
        
        return Promise.resolve();
    }
    
    // Load co-owner options based on selected type
    function loadCoOwnerOptions() {
        const typeSelect = document.getElementById('opportunityCoOwnerType');
        const coOwnerSelect = document.getElementById('opportunityCoOwner');
        const coOwnerGroup = document.getElementById('coOwnerSelectGroup');
        
        const coOwnerType = typeSelect.value;
        
        if (!coOwnerType) {
            coOwnerGroup.style.display = 'none';
            coOwnerSelect.value = '';
            return;
        }
        
        coOwnerGroup.style.display = 'block';
        coOwnerSelect.innerHTML = '<option value="">Select Co-Owner</option>';
        
        if (coOwnerType === 'user') {
            // Populate with CRM users
            users.forEach(u => {
                coOwnerSelect.innerHTML += `<option value="${u.id}">👤 ${u.display_name || u.username}</option>`;
            });
        } else if (coOwnerType === 'federal') {
            // Populate with federal contacts (active only)
            contacts.filter(c => c.status === 'Active').forEach(c => {
                coOwnerSelect.innerHTML += `<option value="${c.id}">🏛️ ${c.firstName} ${c.lastName} - ${c.agencyName || 'No Agency'}</option>`;
            });
        } else if (coOwnerType === 'commercial') {
            // Populate with company/commercial contacts (active only)
            companyContacts.filter(c => c.status === 'Active').forEach(c => {
                coOwnerSelect.innerHTML += `<option value="${c.id}">🏢 ${c.first_name} ${c.last_name} - ${c.companyName || 'No Company'}</option>`;
            });
        }
    }
    
    // =============================================
    // OPPORTUNITY WORKSPACE FUNCTIONS (Shipley-based)
    // =============================================
    
    let currentWorkspaceOppId = null;
    let workspaceData = {
        opportunity: null,
        qualification: null,
        qualification_contacts: [],
        capture: null,
        competitors: [],
        teaming_partners: [],
        bid_decision: null,
        risks: [],
        tasks: []
    };
    let currentWorkspacePhase = 'qualification';
    let qualificationDecision = 'pending';
    let captureDecision = 'pending';
    let finalDecision = 'pending';
    
    async function openOpportunityWorkspace(oppId) {
        currentWorkspaceOppId = oppId;
        
        // Show loading state
        document.getElementById('oppWorkspaceOverlay').classList.add('open');
        document.getElementById('oppWorkspaceTitle').textContent = 'Loading...';
        
        try {
            const response = await fetch(`${API_URL}?action=getOpportunityWorkspace&id=${oppId}`);
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError, 'Response:', text);
                showToast('Error: Invalid response from server. Check console for details.', 'error');
                closeOpportunityWorkspace();
                return;
            }
            
            if (data.success) {
                workspaceData = data;
                renderWorkspace();
            } else {
                showToast('Error loading opportunity: ' + (data.error || 'Unknown error', 'error'));
                closeOpportunityWorkspace();
            }
        } catch (error) {
            console.error('Error loading workspace:', error);
            showToast('Error loading opportunity workspace: ' + error.message, 'error');
            closeOpportunityWorkspace();
        }
    }
    
    function closeOpportunityWorkspace() {
        document.getElementById('oppWorkspaceOverlay').classList.remove('open');
        currentWorkspaceOppId = null;
        workspaceData = { opportunity: null, qualification: null, qualification_contacts: [], capture: null, competitors: [], teaming_partners: [], bid_decision: null, risks: [], tasks: [] };
    }
    
    function renderWorkspace() {
        const opp = workspaceData.opportunity;
        if (!opp) return;
        
        // Header
        document.getElementById('oppWorkspaceTitle').textContent = opp.title || 'Untitled Opportunity';
        document.getElementById('oppWorkspaceSubtitle').textContent = `${opp.agency_name || 'No Agency'} • $${parseFloat(opp.value || 0).toLocaleString()}`;
        
        // Phase status
        const phase = opp.workspace_phase || 'qualification';
        currentWorkspacePhase = phase;
        updatePhaseStatus(phase);
        
        // Populate Qualification data
        populateQualificationForm();
        
        // Populate Capture data
        populateCaptureForm();
        
        // Populate Bid Decision data
        populateBidDecisionForm();
        
        // Calculate progress
        calculateWorkspaceProgress();
        
        // Switch to current phase
        switchWorkspacePhase(phase === 'won' || phase === 'no_bid' ? 'bid_decision' : phase);
    }
    
    function updatePhaseStatus(currentPhase) {
        const phases = ['qualification', 'capture', 'bid_decision'];
        const phaseIndex = phases.indexOf(currentPhase);
        
        document.querySelectorAll('.opp-phase-tab').forEach(tab => {
            const tabPhase = tab.dataset.phase;
            const tabIndex = phases.indexOf(tabPhase);
            
            tab.classList.remove('active', 'completed', 'locked');
            
            if (tabIndex < phaseIndex || currentPhase === 'won' || currentPhase === 'no_bid') {
                tab.classList.add('completed');
                tab.querySelector('.phase-status').textContent = '✓ Complete';
            } else if (tabIndex === phaseIndex) {
                tab.classList.add('active');
                tab.querySelector('.phase-status').textContent = '● Current';
            } else {
                tab.classList.add('locked');
                tab.querySelector('.phase-status').textContent = '🔒 Locked';
            }
        });
        
        // Update header status badge
        const statusBadge = document.getElementById('oppWorkspacePhaseStatus');
        statusBadge.className = 'opp-workspace-status status-' + currentPhase;
        const statusNames = {
            'qualification': 'Qualification',
            'capture': 'Capture',
            'bid_decision': 'Bid Decision',
            'won': 'GO - Proceed',
            'no_bid': 'No Bid'
        };
        statusBadge.textContent = statusNames[currentPhase] || currentPhase;
    }
    
    function switchWorkspacePhase(phase) {
        const tab = document.querySelector(`.opp-phase-tab[data-phase="${phase}"]`);
        if (tab && tab.classList.contains('locked')) {
            showToast('This phase is locked. Complete the previous phase first.', 'warning');
            return;
        }
        
        // Hide all content
        document.querySelectorAll('.opp-phase-content').forEach(c => c.classList.remove('active'));
        
        // Show selected content
        const phaseIds = {
            'qualification': 'phaseQualification',
            'capture': 'phaseCapture',
            'bid_decision': 'phaseBidDecision'
        };
        document.getElementById(phaseIds[phase])?.classList.add('active');
        
        // Update tab styling
        document.querySelectorAll('.opp-phase-tab').forEach(t => {
            if (!t.classList.contains('completed') && !t.classList.contains('locked')) {
                t.classList.remove('active');
            }
        });
        if (tab && !tab.classList.contains('completed')) {
            tab.classList.add('active');
        }
    }
    
    function populateQualificationForm() {
        const qual = workspaceData.qualification || {};
        
        // Profile fields
        document.getElementById('qualSolicitationNumber').value = qual.solicitation_number || '';
        document.getElementById('qualNaicsCode').value = qual.naics_code || '';
        document.getElementById('qualSetAsideType').value = qual.set_aside_type || 'full_open';
        document.getElementById('qualContractType').value = qual.contract_type || 'ffp';
        document.getElementById('qualContractVehicle').value = qual.contract_vehicle || '';
        document.getElementById('qualPeriodOfPerformance').value = qual.period_of_performance || '';
        document.getElementById('qualExpectedRfpDate').value = qual.expected_rfp_date || '';
        document.getElementById('qualExpectedAwardDate').value = qual.expected_award_date || '';
        
        // Scorecard
        document.getElementById('scoreKnowCustomer').value = qual.score_know_customer || 0;
        document.getElementById('scoreWorkedBefore').value = qual.score_worked_before || 0;
        document.getElementById('scoreDecisionMakerAccess').value = qual.score_decision_maker_access || 0;
        document.getElementById('scoreFunded').value = qual.score_funded || 0;
        document.getElementById('scoreUnderstandScope').value = qual.score_understand_scope || 0;
        document.getElementById('scoreRealisticTimeline').value = qual.score_realistic_timeline || 0;
        document.getElementById('scoreKnowIncumbent').value = qual.score_know_incumbent || 0;
        document.getElementById('scoreCanBeatCompetition').value = qual.score_can_beat_competition || 0;
        document.getElementById('scoreTechnicalCapability').value = qual.score_technical_capability || 0;
        document.getElementById('scorePastPerformance').value = qual.score_past_performance || 0;
        
        // Customer intelligence
        document.getElementById('qualCustomerPainPoints').value = qual.customer_pain_points || '';
        document.getElementById('qualHotButtons').value = qual.hot_buttons || '';
        document.getElementById('qualEvaluationPriorities').value = qual.evaluation_priorities || '';
        document.getElementById('qualIncumbentIssues').value = qual.incumbent_issues || '';
        
        // Decision
        qualificationDecision = qual.qualification_decision || 'pending';
        document.getElementById('qualDecisionNotes').value = qual.decision_notes || '';
        updateQualificationDecisionUI();
        
        // Contacts
        renderQualificationContacts();
        
        // Calculate score
        calculateQualificationScore();

        // Phase tasks
        renderWorkspacePhaseTasks('qualification');
    }
    
    function calculateQualificationScore() {
        const weights = {
            'scoreKnowCustomer': 0.15,
            'scoreWorkedBefore': 0.10,
            'scoreDecisionMakerAccess': 0.10,
            'scoreFunded': 0.15,
            'scoreUnderstandScope': 0.10,
            'scoreRealisticTimeline': 0.05,
            'scoreKnowIncumbent': 0.10,
            'scoreCanBeatCompetition': 0.10,
            'scoreTechnicalCapability': 0.10,
            'scorePastPerformance': 0.05
        };
        
        let total = 0;
        for (const [id, weight] of Object.entries(weights)) {
            const score = parseInt(document.getElementById(id)?.value || 0);
            total += (score / 10) * weight * 100;
        }
        
        const scoreDisplay = document.getElementById('qualificationScoreDisplay');
        scoreDisplay.textContent = Math.round(total) + '%';
        scoreDisplay.className = 'opp-scorecard-total-value ' + (total >= 70 ? 'score-high' : total >= 50 ? 'score-medium' : 'score-low');
        
        return total;
    }
    
    function selectQualificationDecision(decision) {
        qualificationDecision = decision;
        updateQualificationDecisionUI();
    }
    
    function updateQualificationDecisionUI() {
        document.querySelectorAll('#phaseQualification .opp-gate-btn').forEach(btn => {
            btn.classList.remove('selected');
            if (btn.dataset.decision === qualificationDecision) {
                btn.classList.add('selected');
            }
        });
    }
    
    function renderQualificationContacts() {
        const tbody = document.getElementById('qualContactsTableBody');
        const qc = workspaceData.qualification_contacts || [];
        
        if (qc.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #999;">No contacts added yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = qc.map(c => `
            <tr>
                <td>${c.firstName || ''} ${c.lastName || ''}</td>
                <td>${c.title || '—'}</td>
                <td>${c.contact_role || '—'}</td>
                <td>${c.notes || '—'}</td>
                <td>
                    <button class="opp-table-btn delete" onclick="deleteQualificationContact(${c.contact_id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    }
    
    async function saveQualificationData() {
        const score = calculateQualificationScore();
        
        // Validate required fields
        const naicsCode = document.getElementById('qualNaicsCode').value.trim();
        const setAsideType = document.getElementById('qualSetAsideType').value;
        const contractType = document.getElementById('qualContractType').value;
        const customerPainPoints = document.getElementById('qualCustomerPainPoints').value.trim();
        
        if (!naicsCode) {
            showToast('Please enter NAICS Code (required field).', 'warning');
            document.getElementById('qualNaicsCode').focus();
            return;
        }
        
        if (!setAsideType) {
            showToast('Please select Set-Aside Type (required field).', 'warning');
            document.getElementById('qualSetAsideType').focus();
            return;
        }
        
        if (!contractType) {
            showToast('Please select Contract Type (required field).', 'warning');
            document.getElementById('qualContractType').focus();
            return;
        }
        
        if (!customerPainPoints) {
            showToast('Please enter Customer Pain Points (required field).', 'warning');
            document.getElementById('qualCustomerPainPoints').focus();
            return;
        }
        
        const data = {
            opportunity_id: currentWorkspaceOppId,
            solicitation_number: document.getElementById('qualSolicitationNumber').value,
            naics_code: naicsCode,
            set_aside_type: setAsideType,
            contract_type: contractType,
            contract_vehicle: document.getElementById('qualContractVehicle').value,
            period_of_performance: document.getElementById('qualPeriodOfPerformance').value,
            expected_rfp_date: document.getElementById('qualExpectedRfpDate').value || null,
            expected_award_date: document.getElementById('qualExpectedAwardDate').value || null,
            score_know_customer: parseInt(document.getElementById('scoreKnowCustomer').value) || 0,
            score_worked_before: parseInt(document.getElementById('scoreWorkedBefore').value) || 0,
            score_decision_maker_access: parseInt(document.getElementById('scoreDecisionMakerAccess').value) || 0,
            score_funded: parseInt(document.getElementById('scoreFunded').value) || 0,
            score_understand_scope: parseInt(document.getElementById('scoreUnderstandScope').value) || 0,
            score_realistic_timeline: parseInt(document.getElementById('scoreRealisticTimeline').value) || 0,
            score_know_incumbent: parseInt(document.getElementById('scoreKnowIncumbent').value) || 0,
            score_can_beat_competition: parseInt(document.getElementById('scoreCanBeatCompetition').value) || 0,
            score_technical_capability: parseInt(document.getElementById('scoreTechnicalCapability').value) || 0,
            score_past_performance: parseInt(document.getElementById('scorePastPerformance').value) || 0,
            customer_pain_points: customerPainPoints,
            hot_buttons: document.getElementById('qualHotButtons').value,
            evaluation_priorities: document.getElementById('qualEvaluationPriorities').value,
            incumbent_issues: document.getElementById('qualIncumbentIssues').value,
            qualification_decision: qualificationDecision,
            decision_notes: document.getElementById('qualDecisionNotes').value
        };
        
        // Validate gate decision - warn if score < 70 but allow proceed
        if (qualificationDecision === 'pursue' && score < 70) {
            if (!confirm('Score is below 70%. Are you sure you want to pursue this opportunity?')) {
                return;
            }
        }
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityQualification`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                // Refresh workspace to show updated phase
                if (qualificationDecision === 'pursue') {
                    showToast('Qualification saved! Moving to Capture phase.', 'success');
                    await openOpportunityWorkspace(currentWorkspaceOppId);
                } else {
                    showToast('Qualification data saved!', 'success');
                    calculateWorkspaceProgress();
                }
            } else {
                showToast('Error saving: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving qualification:', error);
            showToast('Error saving qualification data', 'error');
        }
    }
    
    // Capture Phase Functions
    function populateCaptureForm() {
        const cap = workspaceData.capture || {};
        
        // Win themes
        document.getElementById('capWinTheme1Title').value = cap.win_theme_1_title || '';
        document.getElementById('capWinTheme1Message').value = cap.win_theme_1_message || '';
        document.getElementById('capWinTheme2Title').value = cap.win_theme_2_title || '';
        document.getElementById('capWinTheme2Message').value = cap.win_theme_2_message || '';
        document.getElementById('capWinTheme3Title').value = cap.win_theme_3_title || '';
        document.getElementById('capWinTheme3Message').value = cap.win_theme_3_message || '';
        document.getElementById('capDiscriminators').value = cap.discriminators || '';
        document.getElementById('capGhostingStrategy').value = cap.ghosting_strategy || '';
        
        // Solution
        document.getElementById('capTechnicalApproach').value = cap.technical_approach || '';
        document.getElementById('capManagementApproach').value = cap.management_approach || '';
        document.getElementById('capKeyPersonnel').value = cap.key_personnel_requirements || '';
        document.getElementById('capTeamingStrategy').value = cap.teaming_strategy || 'prime';
        
        // Pricing
        document.getElementById('capPriceToWin').value = cap.price_to_win || '';
        document.getElementById('capPricingStrategy').value = cap.pricing_strategy || 'competitive';
        document.getElementById('capMarginTarget').value = cap.margin_target || '';
        document.getElementById('capCostDrivers').value = cap.cost_drivers || '';
        
        // Milestones
        document.getElementById('milestoneDraftRfpReview').checked = cap.milestone_draft_rfp_review == 1;
        document.getElementById('milestoneIndustryDay').checked = cap.milestone_industry_day == 1;
        document.getElementById('milestoneQuestionsSubmitted').checked = cap.milestone_questions_submitted == 1;
        document.getElementById('milestonePinkTeam').checked = cap.milestone_pink_team == 1;
        document.getElementById('milestoneTeamingSigned').checked = cap.milestone_teaming_signed == 1;
        document.getElementById('milestonePricingApproved').checked = cap.milestone_pricing_approved == 1;
        updateMilestoneUI();
        
        // Decision
        captureDecision = cap.proceed_to_bid || 'pending';
        document.getElementById('capDecisionNotes').value = cap.capture_decision_notes || '';
        updateCaptureDecisionUI();
        
        // Competitors
        renderCompetitors();

        // Teaming partners
        renderTeamingPartners();

        // Phase tasks
        renderWorkspacePhaseTasks('capture');
    }

    function updateMilestoneUI() {
        const milestones = ['DraftRfpReview', 'IndustryDay', 'QuestionsSubmitted', 'PinkTeam', 'TeamingSigned', 'PricingApproved'];
        milestones.forEach(m => {
            const checkbox = document.getElementById('milestone' + m);
            const label = checkbox?.closest('.opp-milestone');
            if (checkbox && label) {
                if (checkbox.checked) {
                    label.classList.add('completed');
                } else {
                    label.classList.remove('completed');
                }
            }
        });
    }
    
    function selectCaptureDecision(decision) {
        captureDecision = decision;
        updateCaptureDecisionUI();
    }
    
    function updateCaptureDecisionUI() {
        document.querySelectorAll('#phaseCapture .opp-gate-btn').forEach(btn => {
            btn.classList.remove('selected');
            if (btn.dataset.decision === captureDecision) {
                btn.classList.add('selected');
            }
        });
    }
    
    function renderCompetitors() {
        const tbody = document.getElementById('competitorsTableBody');
        const comps = workspaceData.competitors || [];
        
        if (comps.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No competitors added yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = comps.map(c => `
            <tr data-id="${c.id}">
                <td><input type="text" value="${escapeHtml(c.competitor_name || '')}" class="comp-name"></td>
                <td><input type="checkbox" ${c.is_incumbent == 1 ? 'checked' : ''} class="comp-incumbent"></td>
                <td><textarea class="comp-strengths">${escapeHtml(c.strengths || '')}</textarea></td>
                <td><textarea class="comp-weaknesses">${escapeHtml(c.weaknesses || '')}</textarea></td>
                <td><input type="number" min="0" max="100" value="${c.win_probability || 0}" class="comp-pwin" style="width: 60px;"></td>
                <td class="opp-table-actions">
                    <button class="opp-table-btn save" onclick="saveCompetitor(${c.id}, this)">💾</button>
                    <button class="opp-table-btn delete" onclick="deleteCompetitor(${c.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    }
    
    function addCompetitorRow() {
        // Build companies dropdown HTML from Company Directory
        const companiesHtml = companies.map(c => {
            const companyName = c.company_name || '';
            const safeCompanyName = companyName.replace(/'/g, "\\'");
            return `
            <div class="dropdown-item" data-name="${companyName}" 
                onclick="selectCompetitorCompany('${safeCompanyName}')"
                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                🏢 ${companyName} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.company_type || 'Company'}</span>
            </div>
        `}).join('');
        
        // Get current competitors for display
        const competitors = workspaceData.competitors || [];
        const assignedListHtml = competitors.length > 0 ? competitors.map(c => `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; margin-bottom: 6px;">
                <div>
                    <span style="font-weight: 500;">${c.competitor_name || 'Unknown'}</span>
                    ${c.is_incumbent ? '<span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px;">INCUMBENT</span>' : ''}
                    <span style="color: #6c757d; font-size: 0.85rem; margin-left: 8px;">Pwin: ${c.win_probability || 0}%</span>
                </div>
                <button onclick="deleteCompetitor(${c.id}); document.getElementById('competitorModal').style.display='none'; setTimeout(() => addCompetitorRow(), 300);" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 0.8rem;">Remove</button>
            </div>
        `).join('') : '<div style="color: #6c757d; font-style: italic;">No competitors added yet</div>';
        
        const html = `
            <div class="modal-content" style="max-width: 550px;">
                <span class="close" onclick="document.getElementById('competitorModal').style.display='none'">&times;</span>
                <h2>Add Competitor</h2>
                
                <div class="form-group" style="margin-bottom: 15px; position: relative;">
                    <label>Search Company Directory *</label>
                    <input type="text" id="competitorSearch" placeholder="Type to search companies or enter name..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;"
                        oninput="filterCompetitorDropdown()" onfocus="showCompetitorDropdown()">
                    <div id="competitorDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 2200;">
                        ${companiesHtml || '<div style="padding: 10px 15px; color: #6c757d; font-style: italic;">No companies available</div>'}
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="competitorIncumbent" style="width: 18px; height: 18px;">
                        <span>This competitor is the <strong>Incumbent</strong></span>
                    </label>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Strengths</label>
                    <textarea id="competitorStrengths" placeholder="What are their competitive advantages?" 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; min-height: 70px;"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Weaknesses (Ghost Points)</label>
                    <textarea id="competitorWeaknesses" placeholder="Weaknesses we can exploit in our proposal..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; min-height: 70px;"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Estimated Probability of Win (Pwin) %</label>
                    <input type="number" id="competitorPwin" min="0" max="100" value="50" 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                
                <button class="btn" onclick="submitCompetitor()" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Add Competitor</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">⚔️ Current Competitors</h4>
                    <div id="currentCompetitors" style="max-height: 200px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('competitorModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'competitorModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        modal.style.zIndex = '2100';
        
        // Close dropdown when clicking outside
        document.addEventListener('click', closeCompetitorDropdownOnOutsideClick);
    }
    
    function closeCompetitorDropdownOnOutsideClick(e) {
        const dropdown = document.getElementById('competitorDropdown');
        const search = document.getElementById('competitorSearch');
        if (dropdown && search && !dropdown.contains(e.target) && e.target !== search) {
            dropdown.style.display = 'none';
        }
    }
    
    function showCompetitorDropdown() {
        const dropdown = document.getElementById('competitorDropdown');
        if (dropdown) dropdown.style.display = 'block';
    }
    
    function filterCompetitorDropdown() {
        const searchTerm = document.getElementById('competitorSearch').value.toLowerCase();
        const dropdown = document.getElementById('competitorDropdown');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function selectCompetitorCompany(companyName) {
        document.getElementById('competitorSearch').value = companyName;
        document.getElementById('competitorDropdown').style.display = 'none';
    }
    
    async function submitCompetitor() {
        const competitorName = document.getElementById('competitorSearch').value;
        const isIncumbent = document.getElementById('competitorIncumbent').checked;
        const strengths = document.getElementById('competitorStrengths').value;
        const weaknesses = document.getElementById('competitorWeaknesses').value;
        const pwin = parseInt(document.getElementById('competitorPwin').value) || 0;
        
        if (!competitorName) {
            showToast('Please enter or select a competitor name.', 'warning');
            return;
        }
        
        const data = {
            opportunity_id: currentWorkspaceOppId,
            competitor_name: competitorName,
            is_incumbent: isIncumbent,
            strengths: strengths,
            weaknesses: weaknesses,
            win_probability: pwin
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompetitor`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                workspaceData.competitors.push({ ...data, id: result.id });
                renderCompetitors();
                document.getElementById('competitorModal').style.display = 'none';
            } else {
                showToast('Error saving competitor: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving competitor:', error);
            showToast('Error saving competitor', 'error');
        }
    }
    
    async function saveCompetitor(id, btn) {
        const row = btn.closest('tr');
        const data = {
            opportunity_id: currentWorkspaceOppId,
            id: id || 0,
            competitor_name: row.querySelector('.comp-name').value,
            is_incumbent: row.querySelector('.comp-incumbent').checked,
            strengths: row.querySelector('.comp-strengths').value,
            weaknesses: row.querySelector('.comp-weaknesses').value,
            win_probability: parseInt(row.querySelector('.comp-pwin').value) || 0
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompetitor`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                row.dataset.id = result.id;
                // Update local data
                const existing = workspaceData.competitors.find(c => c.id == result.id);
                if (existing) {
                    Object.assign(existing, data);
                } else {
                    workspaceData.competitors.push({ ...data, id: result.id });
                }
            } else {
                showToast('Error saving competitor', 'error');
            }
        } catch (error) {
            console.error('Error saving competitor:', error);
        }
    }
    
    async function deleteCompetitor(id) {
        if (!confirm('Delete this competitor?')) return;
        
        try {
            await fetch(`${API_URL}?action=deleteCompetitor`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            workspaceData.competitors = workspaceData.competitors.filter(c => c.id != id);
            renderCompetitors();
        } catch (error) {
            console.error('Error deleting competitor:', error);
        }
    }
    
    function renderTeamingPartners() {
        const tbody = document.getElementById('teamingPartnersTableBody');
        const partners = workspaceData.teaming_partners || [];
        
        if (partners.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No teaming partners added yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = partners.map(p => `
            <tr data-id="${p.id}">
                <td>${p.company_name || p.partner_name || '—'}</td>
                <td>${p.role || '—'}</td>
                <td>${p.capability || '—'}</td>
                <td><span class="status-badge">${p.status || '—'}</span></td>
                <td>${p.teaming_agreement_date || '—'}</td>
                <td class="opp-table-actions">
                    <button class="opp-table-btn delete" onclick="deleteTeamingPartner(${p.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    }
    
    function addTeamingPartnerRow() {
        // Build companies dropdown HTML from Company Directory
        const companiesHtml = companies.map(c => {
            const companyName = c.company_name || '';
            const safeCompanyName = companyName.replace(/'/g, "\\'");
            return `
            <div class="dropdown-item" data-name="${companyName}" 
                onclick="selectTeamingPartnerCompany(${c.id}, '${safeCompanyName}')"
                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                🏢 ${companyName} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.company_type || 'Company'}</span>
            </div>
        `}).join('');
        
        // Get already assigned partners
        const partners = workspaceData.teaming_partners || [];
        const assignedListHtml = partners.length > 0 ? partners.map(p => `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; margin-bottom: 6px;">
                <span>🏢 ${p.partner_name || p.company_name || 'Unknown'} <span style="color: #6c757d; font-size: 0.85rem;">(${p.role || 'No role'})</span></span>
                <button onclick="deleteTeamingPartner(${p.id}); document.getElementById('teamingPartnerModal').style.display='none';" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 0.8rem;">Remove</button>
            </div>
        `).join('') : '<div style="color: #6c757d; font-style: italic;">No teaming partners added yet</div>';
        
        const html = `
            <div class="modal-content" style="max-width: 550px;">
                <span class="close" onclick="document.getElementById('teamingPartnerModal').style.display='none'">&times;</span>
                <h2>Add Teaming Partner</h2>
                
                <div class="form-group" style="margin-bottom: 15px; position: relative;">
                    <label>Select Company from Directory *</label>
                    <input type="text" id="teamingPartnerSearch" placeholder="Type to search companies..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;"
                        oninput="filterTeamingPartnerDropdown()" onfocus="showTeamingPartnerDropdown()">
                    <div id="teamingPartnerDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 2200;">
                        ${companiesHtml || '<div style="padding: 10px 15px; color: #6c757d; font-style: italic;">No companies available</div>'}
                    </div>
                    <input type="hidden" id="teamingPartnerSelectedId">
                    <input type="hidden" id="teamingPartnerSelectedName">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Partner Role *</label>
                    <select id="teamingPartnerRole" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                        <option value="sub">Subcontractor</option>
                        <option value="prime">Prime Contractor</option>
                        <option value="jv_partner">JV Partner</option>
                        <option value="mentor">Mentor</option>
                        <option value="protege">Protégé</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Capability / Contribution *</label>
                    <textarea id="teamingPartnerCapability" placeholder="What capability does this partner bring?" 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; min-height: 80px;"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Status</label>
                    <select id="teamingPartnerStatus" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                        <option value="prospect">Prospect</option>
                        <option value="engaged">Engaged</option>
                        <option value="committed">Committed</option>
                        <option value="signed">Signed</option>
                    </select>
                </div>
                
                <button class="btn" onclick="submitTeamingPartner()" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Add Partner</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">🤝 Current Teaming Partners</h4>
                    <div id="currentTeamingPartners" style="max-height: 200px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('teamingPartnerModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'teamingPartnerModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        modal.style.zIndex = '2100';
        
        // Close dropdown when clicking outside
        document.addEventListener('click', closeTeamingPartnerDropdownOnOutsideClick);
    }
    
    function closeTeamingPartnerDropdownOnOutsideClick(e) {
        const dropdown = document.getElementById('teamingPartnerDropdown');
        const search = document.getElementById('teamingPartnerSearch');
        if (dropdown && search && !dropdown.contains(e.target) && e.target !== search) {
            dropdown.style.display = 'none';
        }
    }
    
    function showTeamingPartnerDropdown() {
        const dropdown = document.getElementById('teamingPartnerDropdown');
        if (dropdown) dropdown.style.display = 'block';
    }
    
    function filterTeamingPartnerDropdown() {
        const searchTerm = document.getElementById('teamingPartnerSearch').value.toLowerCase();
        const dropdown = document.getElementById('teamingPartnerDropdown');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function selectTeamingPartnerCompany(companyId, companyName) {
        document.getElementById('teamingPartnerSearch').value = companyName;
        document.getElementById('teamingPartnerSelectedId').value = companyId;
        document.getElementById('teamingPartnerSelectedName').value = companyName;
        document.getElementById('teamingPartnerDropdown').style.display = 'none';
    }
    
    async function submitTeamingPartner() {
        const companyId = document.getElementById('teamingPartnerSelectedId').value;
        const companyName = document.getElementById('teamingPartnerSelectedName').value || document.getElementById('teamingPartnerSearch').value;
        const role = document.getElementById('teamingPartnerRole').value;
        const capability = document.getElementById('teamingPartnerCapability').value;
        const status = document.getElementById('teamingPartnerStatus').value;

        if (!companyName) {
            showToast('Please select or enter a company name.', 'warning');
            return;
        }

        if (!capability) {
            showToast('Please enter the partner\'s capability/contribution.', 'warning');
            return;
        }

        const success = await saveTeamingPartner({
            opportunity_id: currentWorkspaceOppId,
            company_id: companyId || null,
            partner_name: companyName,
            role: role,
            capability: capability,
            status: status
        });

        // Only close modal on success
        if (success) {
            document.getElementById('teamingPartnerModal').style.display = 'none';
        }
    }

    async function saveTeamingPartner(data) {
        try {
            const response = await fetch(`${API_URL}?action=saveTeamingPartner`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            // Handle non-JSON responses (PHP fatal errors)
            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (parseErr) {
                console.error('Server returned non-JSON:', text);
                showToast('Server error saving partner. Check console for details.', 'error');
                return false;
            }

            if (result.success) {
                // Ensure teaming_partners array exists
                if (!workspaceData.teaming_partners) {
                    workspaceData.teaming_partners = [];
                }
                workspaceData.teaming_partners.push({ ...data, id: result.id });
                renderTeamingPartners();
                return true;
            } else {
                showToast('Error saving partner: ' + (result.error || 'Unknown error', 'error'));
                console.error('saveTeamingPartner error:', result);
                return false;
            }
        } catch (error) {
            showToast('Error saving partner: ' + error.message, 'error');
            console.error('Error saving partner:', error);
            return false;
        }
    }
    
    async function deleteTeamingPartner(id) {
        if (!confirm('Remove this teaming partner?')) return;
        
        try {
            await fetch(`${API_URL}?action=deleteTeamingPartner`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            workspaceData.teaming_partners = workspaceData.teaming_partners.filter(p => p.id != id);
            renderTeamingPartners();
        } catch (error) {
            console.error('Error deleting partner:', error);
        }
    }
    
    function createCaptureMilestoneTasks() {
        // Open the existing task modal with opportunity pre-selected
        openTaskModalForOpportunity(currentWorkspaceOppId, 'capture');
    }

    function openTaskModalForOpportunity(oppId, phase) {
        currentEditId = null;

        // Reset the form
        const form = document.getElementById('taskForm');
        if (form) form.reset();

        // Reset related item fields first
        document.getElementById('taskRelatedItemId').value = '';
        document.getElementById('taskRelatedContactType').value = '';
        document.getElementById('taskWorkspacePhase').value = phase || '';
        document.getElementById('relatedItemGroup').style.display = 'none';
        document.getElementById('selectedRelatedItem').style.display = 'none';
        document.getElementById('selectedRelatedItem').innerHTML = '';
        document.getElementById('relatedItemSearch').value = '';

        // Set default values
        document.getElementById('taskStatus').value = 'To Do';
        document.getElementById('taskPriority').value = 'Medium';
        document.getElementById('taskAssignedTo').value = '';

        // Pre-fill Related To as Opportunity
        document.getElementById('taskRelatedTo').value = 'Opportunity';

        // Trigger the related item options update and then select the opportunity
        updateRelatedItemOptions().then(() => {
            // Set the related item ID
            document.getElementById('taskRelatedItemId').value = oppId;

            // Find the opportunity name to display
            const opp = opportunities.find(o => o.id == oppId);
            const oppName = opp ? opp.title : 'Opportunity #' + oppId;

            // Show the selected item display
            const selectedDisplay = document.getElementById('selectedRelatedItem');
            selectedDisplay.innerHTML = `
                <div class="item-info">
                    <div class="item-name">${oppName}</div>
                    <div class="item-detail">Opportunity</div>
                </div>
                <button type="button" class="remove-btn" onclick="clearRelatedItem()">×</button>
            `;
            selectedDisplay.style.display = 'flex';
            document.getElementById('relatedItemSearch').style.display = 'none';
            document.getElementById('relatedItemGroup').style.display = 'block';
        });

        // Update modal title with phase name
        const phaseNames = { qualification: 'Qualification', capture: 'Capture', bid_decision: 'Bid Decision' };
        const phaseLabel = phase ? ` - ${phaseNames[phase] || phase}` : '';
        document.getElementById('taskModalTitle').textContent = `Add Task for Opportunity${phaseLabel}`;

        // Open the modal with higher z-index to appear above workspace overlay
        const taskModal = document.getElementById('taskModal');
        taskModal.style.display = 'block';
        taskModal.style.zIndex = '2100'; // Higher than workspace overlay (2000)
    }

    function renderWorkspacePhaseTasks(phase) {
        const phaseOrder = ['qualification', 'capture', 'bid_decision'];
        const phaseIndex = phaseOrder.indexOf(phase);
        const phaseNames = { qualification: 'Qualification', capture: 'Capture', bid_decision: 'Bid Decision' };
        const allTasks = workspaceData.tasks || [];

        // Tasks belonging to this phase + incomplete tasks from earlier phases
        const phaseTasks = allTasks.filter(t => {
            if (t.workspace_phase === phase) return true;
            // Carry forward incomplete tasks from earlier phases
            if (t.status !== 'Done' && t.status !== 'Completed') {
                const taskPhaseIndex = phaseOrder.indexOf(t.workspace_phase);
                if (taskPhaseIndex >= 0 && taskPhaseIndex < phaseIndex) return true;
            }
            // Tasks with no phase assigned (legacy) show in qualification
            if (!t.workspace_phase && phase === 'qualification') return true;
            return false;
        });

        const tbodyIds = { qualification: 'qualTasksTableBody', capture: 'capTasksTableBody', bid_decision: 'bidTasksTableBody' };
        const tbody = document.getElementById(tbodyIds[phase]);
        if (!tbody) return;

        if (phaseTasks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">No tasks yet</td></tr>';
            return;
        }

        const priorityColors = { High: '#dc3545', Medium: '#fd7e14', Low: '#28a745' };
        const statusColors = { 'To Do': '#6c757d', 'In Progress': '#007bff', 'Done': '#28a745', 'Completed': '#28a745', 'Review': '#6f42c1', 'Pending': '#fd7e14' };

        tbody.innerHTML = phaseTasks.map(t => {
            const isCarryForward = t.workspace_phase && t.workspace_phase !== phase;
            const carryBadge = isCarryForward ? `<span style="font-size:0.7rem;background:#e3f2fd;color:#1565c0;padding:2px 6px;border-radius:3px;margin-left:6px;">${phaseNames[t.workspace_phase] || t.workspace_phase}</span>` : '';
            const pColor = priorityColors[t.priority] || '#6c757d';
            const sColor = statusColors[t.status] || '#6c757d';
            const dueDate = t.dueDate ? new Date(t.dueDate).toLocaleDateString() : '-';
            const assignee = t.assigned_to_name || t.assignedTo || '-';

            return `<tr>
                <td>${t.title}${carryBadge}</td>
                <td>${assignee}</td>
                <td>${dueDate}</td>
                <td><span style="color:${pColor};font-weight:500;">${t.priority || '-'}</span></td>
                <td><span style="background:${sColor};color:#fff;padding:2px 8px;border-radius:4px;font-size:0.8rem;">${t.status || '-'}</span></td>
                <td><button class="opp-table-btn" onclick="editWorkspaceTask(${t.id})">Edit</button> <button class="opp-table-btn" style="color:#dc3545;" onclick="deleteWorkspaceTask(${t.id}, '${t.title.replace(/'/g, "\\'")}')">Delete</button></td>
            </tr>`;
        }).join('');
    }

    function editWorkspaceTask(taskId) {
        const task = (workspaceData.tasks || []).find(t => t.id == taskId);
        if (!task) return;
        currentEditId = task.id;
        document.getElementById('taskId').value = task.id;
        document.getElementById('taskTitle').value = task.title || '';
        document.getElementById('taskRelatedTo').value = 'Opportunity';
        document.getElementById('taskRelatedItemId').value = task.related_item_id || '';
        document.getElementById('taskRelatedContactType').value = task.related_contact_type || '';
        document.getElementById('taskWorkspacePhase').value = task.workspace_phase || '';
        document.getElementById('taskDueDate').value = task.dueDate || '';
        document.getElementById('taskPriority').value = task.priority || 'Medium';
        document.getElementById('taskStatus').value = task.status || 'To Do';
        document.getElementById('taskAssignedTo').value = task.assigned_to_user_id || '';
        document.getElementById('taskDescription').value = task.description || '';

        updateRelatedItemOptions(true);
        if (task.related_item_id) {
            selectRelatedItemById('Opportunity', task.related_item_id);
        }

        document.getElementById('taskModalTitle').textContent = 'Edit Task';
        const taskModal = document.getElementById('taskModal');
        taskModal.style.display = 'block';
        taskModal.style.zIndex = '2100';
    }

    async function deleteWorkspaceTask(taskId, taskTitle) {
        if (!confirm(`Delete task "${taskTitle}"?`)) return;
        try {
            const response = await fetch(`${API_URL}?action=delete&type=task&id=${taskId}`, { method: 'GET' });
            if (!response.ok) throw new Error('Delete failed');
            // Remove from local data immediately
            workspaceData.tasks = workspaceData.tasks.filter(t => t.id != taskId);
            renderWorkspacePhaseTasks('qualification');
            renderWorkspacePhaseTasks('capture');
            renderWorkspacePhaseTasks('bid_decision');
            await fetchAllData();
        } catch (error) {
            showToast('Error deleting task: ' + error.message, 'error');
        }
    }

    function refreshWorkspaceTasks() {
        // Update workspaceData.tasks from the global tasks array (already refreshed by fetchAllData)
        const oppId = currentWorkspaceOppId;
        const oppTasks = tasks.filter(t => (t.relatedTo || '').toLowerCase() === 'opportunity' && t.related_item_id == oppId);
        workspaceData.tasks = oppTasks;
        renderWorkspacePhaseTasks('qualification');
        renderWorkspacePhaseTasks('capture');
        renderWorkspacePhaseTasks('bid_decision');
    }

    async function saveCaptureData() {
        const data = {
            opportunity_id: currentWorkspaceOppId,
            win_theme_1_title: document.getElementById('capWinTheme1Title').value,
            win_theme_1_message: document.getElementById('capWinTheme1Message').value,
            win_theme_2_title: document.getElementById('capWinTheme2Title').value,
            win_theme_2_message: document.getElementById('capWinTheme2Message').value,
            win_theme_3_title: document.getElementById('capWinTheme3Title').value,
            win_theme_3_message: document.getElementById('capWinTheme3Message').value,
            discriminators: document.getElementById('capDiscriminators').value,
            ghosting_strategy: document.getElementById('capGhostingStrategy').value,
            technical_approach: document.getElementById('capTechnicalApproach').value,
            management_approach: document.getElementById('capManagementApproach').value,
            key_personnel_requirements: document.getElementById('capKeyPersonnel').value,
            teaming_strategy: document.getElementById('capTeamingStrategy').value,
            price_to_win: parseFloat(document.getElementById('capPriceToWin').value) || null,
            pricing_strategy: document.getElementById('capPricingStrategy').value,
            margin_target: parseFloat(document.getElementById('capMarginTarget').value) || null,
            cost_drivers: document.getElementById('capCostDrivers').value,
            milestone_draft_rfp_review: document.getElementById('milestoneDraftRfpReview').checked,
            milestone_industry_day: document.getElementById('milestoneIndustryDay').checked,
            milestone_questions_submitted: document.getElementById('milestoneQuestionsSubmitted').checked,
            milestone_pink_team: document.getElementById('milestonePinkTeam').checked,
            milestone_teaming_signed: document.getElementById('milestoneTeamingSigned').checked,
            milestone_pricing_approved: document.getElementById('milestonePricingApproved').checked,
            proceed_to_bid: captureDecision,
            capture_decision_notes: document.getElementById('capDecisionNotes').value
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityCapture`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('Capture data saved!', 'success');
                if (captureDecision === 'yes') {
                    await openOpportunityWorkspace(currentWorkspaceOppId);
                }
                calculateWorkspaceProgress();
            } else {
                showToast('Error saving: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving capture:', error);
            showToast('Error saving capture data', 'error');
        }
    }
    
    // Bid Decision Phase Functions
    function populateBidDecisionForm() {
        const bid = workspaceData.bid_decision || {};
        
        // Scorecard
        document.getElementById('bidScoreStrategicAlignment').value = bid.score_strategic_alignment || 1;
        document.getElementById('bidScoreNewMarket').value = bid.score_new_market || 1;
        document.getElementById('bidScoreCompetitivePosition').value = bid.score_competitive_position || 1;
        document.getElementById('bidScoreCustomerRelationship').value = bid.score_customer_relationship || 1;
        document.getElementById('bidScoreSolutionReadiness').value = bid.score_solution_readiness || 1;
        document.getElementById('bidScoreProposalTeam').value = bid.score_proposal_team || 1;
        document.getElementById('bidScoreKeyPersonnel').value = bid.score_key_personnel || 1;
        document.getElementById('bidScoreSmesAvailable').value = bid.score_smes_available || 1;
        document.getElementById('bidScoreTechnicalRisk').value = bid.score_technical_risk || 1;
        document.getElementById('bidScoreCostScheduleRisk').value = bid.score_cost_schedule_risk || 1;
        document.getElementById('bidScoreContractTerms').value = bid.score_contract_terms || 1;
        document.getElementById('bidScoreAcceptableMargin').value = bid.score_acceptable_margin || 1;
        document.getElementById('bidScoreBpBudget').value = bid.score_bp_budget || 1;
        document.getElementById('bidScoreRevenuePotential').value = bid.score_revenue_potential || 1;
        
        // Resources
        populateUserDropdown('bidProposalManager', bid.proposal_manager_id);
        document.getElementById('bidTechWritersNeeded').value = bid.technical_writers_needed || 0;
        document.getElementById('bidTechWritersAvailable').value = bid.technical_writers_available || 0;
        document.getElementById('bidSmesNeeded').value = bid.smes_needed || 0;
        document.getElementById('bidSmesAvailable').value = bid.smes_available || 0;
        document.getElementById('bidBpBudgetNeeded').value = bid.bp_budget_needed || 0;
        document.getElementById('bidBpBudgetAvailable').value = bid.bp_budget_available || 0;
        
        // Decision
        document.getElementById('bidRecommendation').value = bid.recommendation || 'pending';
        document.getElementById('bidConditions').value = bid.conditions || '';
        document.getElementById('bidJustification').value = bid.justification || '';
        finalDecision = bid.final_decision || 'pending';
        document.getElementById('bidLessonsLearned').value = bid.lessons_learned || '';
        document.getElementById('bidRevisitDate').value = bid.revisit_date || '';
        
        updateBidRecommendationUI();
        updateFinalDecisionUI();
        calculateBidDecisionScore();
        
        // Risks
        renderRisks();

        // Phase tasks
        renderWorkspacePhaseTasks('bid_decision');
    }

    function populateUserDropdown(elementId, selectedId) {
        const select = document.getElementById(elementId);
        select.innerHTML = '<option value="">Select...</option>' + 
            users.map(u => `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.display_name || u.username}</option>`).join('');
    }
    
    function calculateBidDecisionScore() {
        const weights = {
            'bidScoreStrategicAlignment': 0.075,
            'bidScoreNewMarket': 0.075,
            'bidScoreCompetitivePosition': 0.085,
            'bidScoreCustomerRelationship': 0.085,
            'bidScoreSolutionReadiness': 0.08,
            'bidScoreProposalTeam': 0.07,
            'bidScoreKeyPersonnel': 0.065,
            'bidScoreSmesAvailable': 0.065,
            'bidScoreTechnicalRisk': 0.07,
            'bidScoreCostScheduleRisk': 0.065,
            'bidScoreContractTerms': 0.065,
            'bidScoreAcceptableMargin': 0.07,
            'bidScoreBpBudget': 0.065,
            'bidScoreRevenuePotential': 0.065
        };
        
        let total = 0;
        for (const [id, weight] of Object.entries(weights)) {
            const score = parseInt(document.getElementById(id)?.value || 1);
            total += (score / 5) * weight * 100;
        }
        
        const scoreDisplay = document.getElementById('bidDecisionScoreDisplay');
        scoreDisplay.textContent = Math.round(total) + '%';
        scoreDisplay.className = 'opp-scorecard-total-value ' + (total >= 70 ? 'score-high' : total >= 50 ? 'score-medium' : 'score-low');
        
        return total;
    }
    
    function updateBidRecommendationUI() {
        const rec = document.getElementById('bidRecommendation').value;
        document.getElementById('bidConditionsGroup').style.display = rec === 'conditional' ? 'block' : 'none';
    }
    
    function selectFinalDecision(decision) {
        finalDecision = decision;
        updateFinalDecisionUI();
    }
    
    function updateFinalDecisionUI() {
        document.querySelectorAll('#phaseBidDecision .opp-gate-btn').forEach(btn => {
            btn.classList.remove('selected');
            if (btn.dataset.decision === finalDecision) {
                btn.classList.add('selected');
            }
        });
        
        const postActions = document.getElementById('bidPostDecisionActions');
        const convertBtn = document.getElementById('convertToProposalBtn');
        const noGoFields = document.getElementById('noGoFields');
        
        if (finalDecision === 'go') {
            postActions.style.display = 'block';
            convertBtn.style.display = 'inline-block';
            noGoFields.style.display = 'none';
        } else if (finalDecision === 'no_go') {
            postActions.style.display = 'block';
            convertBtn.style.display = 'none';
            noGoFields.style.display = 'block';
        } else {
            postActions.style.display = 'none';
        }
    }
    
    function renderRisks() {
        const tbody = document.getElementById('risksTableBody');
        const risks = workspaceData.risks || [];
        
        if (risks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No risks identified yet</td></tr>';
            return;
        }
        
        tbody.innerHTML = risks.map(r => `
            <tr data-id="${r.id}">
                <td>${r.risk_description || '—'}</td>
                <td><span class="status-badge">${r.probability || '—'}</span></td>
                <td><span class="status-badge">${r.impact || '—'}</span></td>
                <td>${r.mitigation || '—'}</td>
                <td>${r.owner_name || '—'}</td>
                <td class="opp-table-actions">
                    <button class="opp-table-btn delete" onclick="deleteRisk(${r.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    }
    
    function addRiskRow() {
        // Get current risks for display
        const risks = workspaceData.risks || [];
        const assignedListHtml = risks.length > 0 ? risks.map(r => {
            const probColor = r.probability === 'high' ? '#dc3545' : (r.probability === 'medium' ? '#ffc107' : '#28a745');
            const impactColor = r.impact === 'high' ? '#dc3545' : (r.impact === 'medium' ? '#ffc107' : '#28a745');
            return `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 12px; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px; border-left: 4px solid ${probColor};">
                <div style="flex: 1;">
                    <div style="font-weight: 500; margin-bottom: 4px;">${r.risk_description || 'No description'}</div>
                    <div style="font-size: 0.85rem; color: #6c757d;">
                        <span style="color: ${probColor};">P: ${r.probability || 'N/A'}</span> | 
                        <span style="color: ${impactColor};">I: ${r.impact || 'N/A'}</span> | 
                        Status: ${r.status || 'open'}
                    </div>
                </div>
                <button onclick="deleteRisk(${r.id}); document.getElementById('riskModal').style.display='none'; setTimeout(() => addRiskRow(), 300);" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 0.8rem; margin-left: 10px;">Remove</button>
            </div>
        `}).join('') : '<div style="color: #6c757d; font-style: italic;">No risks identified yet</div>';
        
        // Build users dropdown for risk owner
        const usersHtml = users.map(u => `
            <option value="${u.id}">${u.display_name || u.username}</option>
        `).join('');
        
        const html = `
            <div class="modal-content" style="max-width: 550px;">
                <span class="close" onclick="document.getElementById('riskModal').style.display='none'">&times;</span>
                <h2>Add Risk to Register</h2>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Risk Description *</label>
                    <textarea id="riskDescription" placeholder="Describe the risk..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; min-height: 80px;"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Probability *</label>
                        <select id="riskProbability" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Impact *</label>
                        <select id="riskImpact" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                            <option value="low">🟢 Low</option>
                            <option value="medium" selected>🟡 Medium</option>
                            <option value="high">🔴 High</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Mitigation Strategy *</label>
                    <textarea id="riskMitigation" placeholder="How will this risk be mitigated?" 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; min-height: 60px;"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Risk Owner</label>
                        <select id="riskOwner" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                            <option value="">-- Select Owner --</option>
                            ${usersHtml}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="riskStatus" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                            <option value="open" selected>Open</option>
                            <option value="mitigating">Mitigating</option>
                            <option value="accepted">Accepted</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                
                <button class="btn" onclick="submitRisk()" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Add Risk</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">⚠️ Current Risk Register</h4>
                    <div id="currentRisks" style="max-height: 250px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('riskModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'riskModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        modal.style.zIndex = '2100';
    }
    
    function submitRisk() {
        const description = document.getElementById('riskDescription').value;
        const probability = document.getElementById('riskProbability').value;
        const impact = document.getElementById('riskImpact').value;
        const mitigation = document.getElementById('riskMitigation').value;
        const ownerId = document.getElementById('riskOwner').value;
        const status = document.getElementById('riskStatus').value;
        
        if (!description) {
            showToast('Please enter a risk description.', 'warning');
            return;
        }
        
        if (!mitigation) {
            showToast('Please enter a mitigation strategy.', 'warning');
            return;
        }
        
        saveRisk({
            opportunity_id: currentWorkspaceOppId,
            risk_description: description,
            probability: probability,
            impact: impact,
            mitigation: mitigation,
            owner_user_id: ownerId || null,
            status: status
        });
        
        document.getElementById('riskModal').style.display = 'none';
    }
    
    async function saveRisk(data) {
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityRisk`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                workspaceData.risks.push({ ...data, id: result.id });
                renderRisks();
            }
        } catch (error) {
            console.error('Error saving risk:', error);
        }
    }
    
    async function deleteRisk(id) {
        if (!confirm('Delete this risk?')) return;
        
        try {
            await fetch(`${API_URL}?action=deleteOpportunityRisk`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            workspaceData.risks = workspaceData.risks.filter(r => r.id != id);
            renderRisks();
        } catch (error) {
            console.error('Error deleting risk:', error);
        }
    }
    
    async function saveBidDecisionData() {
        const score = calculateBidDecisionScore();
        
        const data = {
            opportunity_id: currentWorkspaceOppId,
            score_strategic_alignment: parseInt(document.getElementById('bidScoreStrategicAlignment').value) || 1,
            score_new_market: parseInt(document.getElementById('bidScoreNewMarket').value) || 1,
            score_competitive_position: parseInt(document.getElementById('bidScoreCompetitivePosition').value) || 1,
            score_customer_relationship: parseInt(document.getElementById('bidScoreCustomerRelationship').value) || 1,
            score_solution_readiness: parseInt(document.getElementById('bidScoreSolutionReadiness').value) || 1,
            score_proposal_team: parseInt(document.getElementById('bidScoreProposalTeam').value) || 1,
            score_key_personnel: parseInt(document.getElementById('bidScoreKeyPersonnel').value) || 1,
            score_smes_available: parseInt(document.getElementById('bidScoreSmesAvailable').value) || 1,
            score_technical_risk: parseInt(document.getElementById('bidScoreTechnicalRisk').value) || 1,
            score_cost_schedule_risk: parseInt(document.getElementById('bidScoreCostScheduleRisk').value) || 1,
            score_contract_terms: parseInt(document.getElementById('bidScoreContractTerms').value) || 1,
            score_acceptable_margin: parseInt(document.getElementById('bidScoreAcceptableMargin').value) || 1,
            score_bp_budget: parseInt(document.getElementById('bidScoreBpBudget').value) || 1,
            score_revenue_potential: parseInt(document.getElementById('bidScoreRevenuePotential').value) || 1,
            proposal_manager_id: document.getElementById('bidProposalManager').value || null,
            technical_writers_needed: parseInt(document.getElementById('bidTechWritersNeeded').value) || 0,
            technical_writers_available: parseInt(document.getElementById('bidTechWritersAvailable').value) || 0,
            smes_needed: parseInt(document.getElementById('bidSmesNeeded').value) || 0,
            smes_available: parseInt(document.getElementById('bidSmesAvailable').value) || 0,
            bp_budget_needed: parseFloat(document.getElementById('bidBpBudgetNeeded').value) || 0,
            bp_budget_available: parseFloat(document.getElementById('bidBpBudgetAvailable').value) || 0,
            recommendation: document.getElementById('bidRecommendation').value,
            conditions: document.getElementById('bidConditions').value,
            final_decision: finalDecision,
            justification: document.getElementById('bidJustification').value,
            lessons_learned: document.getElementById('bidLessonsLearned').value,
            revisit_date: document.getElementById('bidRevisitDate').value || null
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityBidDecision`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                // If decision is "Go", automatically convert to proposal
                if (finalDecision === 'go') {
                    await doConvertToProposal();
                } else if (finalDecision === 'no_go') {
                    showToast('Bid decision saved. Opportunity marked as No-Bid.', 'success');
                    await fetchAllData();
                    closeOpportunityWorkspace();
                } else {
                    showToast('Bid decision saved!', 'success');
                    await fetchAllData();
                    await openOpportunityWorkspace(currentWorkspaceOppId);
                }
            } else {
                showToast('Error saving: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving bid decision:', error);
            showToast('Error saving bid decision', 'error');
        }
    }
    
    // Internal function to convert to proposal (called automatically on Go decision)
    async function doConvertToProposal() {
        try {
            const response = await fetch(`${API_URL}?action=convertOpportunityToProposal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    opportunity_id: currentWorkspaceOppId,
                    winProbability: 50, // Default value
                    submitDate: new Date().toISOString().split('T')[0],
                    dueDate: null
                })
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('Bid Decision: GO! Proposal created successfully.', 'success');
                await fetchAllData();
                closeOpportunityWorkspace();
            } else {
                showToast('Bid decision saved, but error creating proposal: ' + (result.error || 'Failed to convert'), 'warning');
            }
        } catch (error) {
            console.error('Error converting to proposal:', error);
            showToast('Bid decision saved, but error converting to proposal.', 'warning');
        }
    }
    
    async function convertToProposal() {
        if (!confirm('Convert this opportunity to a proposal? This will create a new proposal linked to this opportunity.')) return;
        
        // Use existing conversion function
        const opp = workspaceData.opportunity;
        try {
            const response = await fetch(`${API_URL}?action=convertOpportunityToProposal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    opportunity_id: currentWorkspaceOppId,
                    winProbability: 50,
                    submitDate: new Date().toISOString().split('T')[0],
                    dueDate: null
                })
            });
            const result = await response.json();
            
            if (result.success) {
                showToast('Proposal created successfully!', 'success');
                await fetchAllData();
                closeOpportunityWorkspace();
            } else {
                showToast('Error: ' + (result.error || 'Failed to convert', 'error'));
            }
        } catch (error) {
            console.error('Error converting to proposal:', error);
            showToast('Error converting to proposal', 'error');
        }
    }
    
    function calculateWorkspaceProgress() {
        const opp = workspaceData.opportunity;
        if (!opp) return;
        
        let progress = 0;
        const phase = opp.workspace_phase || 'qualification';
        
        if (phase === 'qualification') {
            progress = 15; // Started
            if (workspaceData.qualification?.qualification_score > 50) progress = 25;
        } else if (phase === 'capture') {
            progress = 40;
            if (workspaceData.capture?.capture_readiness_score > 50) progress = 55;
        } else if (phase === 'bid_decision') {
            progress = 70;
            if (workspaceData.bid_decision?.bid_decision_score > 50) progress = 85;
        } else if (phase === 'won' || phase === 'no_bid') {
            progress = 100;
        }
        
        document.getElementById('oppWorkspaceProgressFill').style.width = progress + '%';
        document.getElementById('oppWorkspaceProgressText').textContent = progress + '%';
    }
    
    function showAddQualContactModal() {
        // Build contacts dropdown HTML (Federal contacts only for qualification)
        const activeContacts = contacts.filter(c => c.status === 'Active' || !c.status);
        const contactsHtml = activeContacts.map(c => {
            const firstName = c.firstName || '';
            const lastName = c.lastName || '';
            const fullName = `${firstName} ${lastName}`.trim();
            const safeName = fullName.replace(/'/g, "\\'");
            return `
            <div class="dropdown-item" data-name="${fullName}" 
                onclick="selectQualContact(${c.id}, '${safeName}')"
                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                🏛️ ${fullName} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.agencyName || 'No Agency'}</span>
            </div>
        `}).join('');
        
        // Get already assigned contacts
        const qualContacts = workspaceData.qualification_contacts || [];
        const assignedListHtml = qualContacts.length > 0 ? qualContacts.map(qc => {
            const firstName = qc.firstName || '';
            const lastName = qc.lastName || '';
            const fullName = `${firstName} ${lastName}`.trim();
            return `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; margin-bottom: 6px;">
                <span>🏛️ ${fullName} <span style="color: #6c757d; font-size: 0.85rem;">(${qc.contact_role || 'No role'})</span></span>
                <button onclick="deleteQualificationContact(${qc.contact_id})" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 0.8rem;">Remove</button>
            </div>
        `}).join('') : '<div style="color: #6c757d; font-style: italic;">No contacts assigned yet</div>';
        
        const html = `
            <div class="modal-content" style="max-width: 500px;">
                <span class="close" onclick="document.getElementById('qualContactModal').style.display='none'">&times;</span>
                <h2>Add Decision Maker / Influencer</h2>
                
                <div class="form-group" style="margin-bottom: 15px; position: relative;">
                    <label>Search Contact *</label>
                    <input type="text" id="qualContactSearch" placeholder="Type to search contacts..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;"
                        oninput="filterQualContactDropdown()" onfocus="showQualContactDropdown()">
                    <div id="qualContactDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 2200;">
                        ${contactsHtml || '<div style="padding: 10px 15px; color: #6c757d; font-style: italic;">No contacts available</div>'}
                    </div>
                    <input type="hidden" id="qualContactSelectedId">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Contact Role *</label>
                    <select id="qualContactRole" style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                        <option value="decision_maker">Decision Maker</option>
                        <option value="influencer">Influencer</option>
                        <option value="evaluator">Evaluator</option>
                        <option value="end_user">End User</option>
                        <option value="technical_poc">Technical POC</option>
                        <option value="contracting_officer">Contracting Officer</option>
                    </select>
                </div>
                
                <button class="btn" onclick="addQualContact()" style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Add Contact</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">👥 Currently Assigned Contacts</h4>
                    <div id="currentQualContacts" style="max-height: 200px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('qualContactModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'qualContactModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        modal.style.zIndex = '2100'; // Higher than workspace overlay (2000)
        
        // Close dropdown when clicking outside
        document.addEventListener('click', closeQualContactDropdownOnOutsideClick);
    }
    
    function closeQualContactDropdownOnOutsideClick(e) {
        const dropdown = document.getElementById('qualContactDropdown');
        const search = document.getElementById('qualContactSearch');
        if (dropdown && search && !dropdown.contains(e.target) && e.target !== search) {
            dropdown.style.display = 'none';
        }
    }
    
    function showQualContactDropdown() {
        const dropdown = document.getElementById('qualContactDropdown');
        if (dropdown) dropdown.style.display = 'block';
    }
    
    function filterQualContactDropdown() {
        const searchTerm = document.getElementById('qualContactSearch').value.toLowerCase();
        const dropdown = document.getElementById('qualContactDropdown');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function selectQualContact(contactId, contactName) {
        document.getElementById('qualContactSearch').value = contactName;
        document.getElementById('qualContactSelectedId').value = contactId;
        document.getElementById('qualContactDropdown').style.display = 'none';
    }
    
    async function addQualContact() {
        const contactId = document.getElementById('qualContactSelectedId').value;
        const role = document.getElementById('qualContactRole').value;
        
        if (!contactId) {
            showToast('Please select a contact.', 'warning');
            return;
        }
        
        // Check if already assigned
        const assignedIds = (workspaceData.qualification_contacts || []).map(qc => qc.contact_id);
        if (assignedIds.includes(parseInt(contactId))) {
            showToast('This contact is already assigned.', 'info');
            return;
        }
        
        await saveQualificationContact(contactId, role);
        document.getElementById('qualContactModal').style.display = 'none';
    }
    
    async function saveQualificationContact(contactId, role) {
        try {
            const response = await fetch(`${API_URL}?action=saveQualificationContact`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentWorkspaceOppId,
                    contact_id: contactId,
                    contact_type: 'federal',
                    contact_role: role
                })
            });
            const result = await response.json();
            
            if (result.success) {
                // Add contact to local data and re-render just the contacts table
                const contact = contacts.find(c => c.id == contactId);
                if (contact) {
                    workspaceData.qualification_contacts = workspaceData.qualification_contacts || [];
                    workspaceData.qualification_contacts.push({
                        contact_id: parseInt(contactId),
                        contact_type: 'federal',
                        contact_role: role,
                        firstName: contact.firstName,
                        lastName: contact.lastName,
                        title: contact.title,
                        email: contact.email,
                        agencyName: contact.agencyName
                    });
                    renderQualificationContacts();
                }
            } else {
                showToast('Error adding contact: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving contact:', error);
            showToast('Error adding contact', 'error');
        }
    }
    
    async function deleteQualificationContact(contactId) {
        if (!confirm('Remove this contact?')) return;
        
        try {
            await fetch(`${API_URL}?action=deleteQualificationContact`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentWorkspaceOppId,
                    contact_id: contactId
                })
            });
            workspaceData.qualification_contacts = workspaceData.qualification_contacts.filter(c => c.contact_id != contactId);
            renderQualificationContacts();
        } catch (error) {
            console.error('Error deleting contact:', error);
        }
    }
    
    // =============================================
    // OPPORTUNITY PANEL FUNCTIONS
    // =============================================
    
    async function openOpportunityPanel(oppId) {
        currentOpportunityId = oppId;
        const opp = opportunities.find(o => o.id == oppId);
        if (!opp) return;
        
        // Set header info
        document.getElementById('oppPanelTitle').textContent = opp.title || 'Untitled Opportunity';
        document.getElementById('oppPanelSubtitle').textContent = `${opp.agencyName || 'No Agency'} - ${opp.status || ''}`;
        
        // Load full opportunity details including assigned users/contacts
        await loadOpportunityDetails(oppId);
        
        // Populate note filter dropdowns
        populateOppNoteFilters();
        
        // Load notes
        loadOpportunityNotes();
        
        // Switch to info tab by default
        switchOppTab('info');
        
        // Show panel
        document.getElementById('oppPanelOverlay').classList.add('open');
        document.getElementById('oppPanel').classList.add('open');
    }
    
    function openOpportunityNotes(oppId) {
        currentOpportunityId = oppId;
        const opp = opportunities.find(o => o.id == oppId);
        if (!opp) return;
        
        // Set header info
        document.getElementById('oppPanelTitle').textContent = opp.title || 'Untitled Opportunity';
        document.getElementById('oppPanelSubtitle').textContent = `${opp.agencyName || 'No Agency'} - ${opp.status || ''}`;
        
        // Load full opportunity details
        loadOpportunityDetails(oppId);
        
        // Populate note filter dropdowns
        populateOppNoteFilters();
        
        // Load notes
        loadOpportunityNotes();
        
        // Switch to NOTES tab directly
        switchOppTab('notes');
        
        // Show panel
        document.getElementById('oppPanelOverlay').classList.add('open');
        document.getElementById('oppPanel').classList.add('open');
    }
    
    function closeOpportunityPanel() {
        document.getElementById('oppPanelOverlay').classList.remove('open');
        document.getElementById('oppPanel').classList.remove('open');
        currentOpportunityId = null;
        currentOpportunityData = null;
        currentOpportunityNotes = [];
    }
    
    function switchOppTab(tab) {
        document.querySelectorAll('#oppPanel .contact-panel-tab').forEach(t => {
            t.classList.remove('active');
            if ((tab === 'info' && t.textContent === 'Info') || (tab === 'notes' && t.textContent === 'Notes')) {
                t.classList.add('active');
            }
        });
        
        if (tab === 'info') {
            document.getElementById('oppInfoSection').style.display = 'block';
            document.getElementById('oppNotesSection').style.display = 'none';
        } else {
            document.getElementById('oppInfoSection').style.display = 'none';
            document.getElementById('oppNotesSection').style.display = 'block';
        }
    }
    
    async function loadOpportunityDetails(oppId) {
        try {
            const response = await fetch(`${API_URL}?action=getOpportunityDetails&id=${oppId}`);
            const data = await response.json();
            
            if (data.success) {
                currentOpportunityData = data.opportunity;
                renderOpportunityInfo();
            }
        } catch (error) {
            console.error('Error loading opportunity details:', error);
        }
    }
    
    function renderOpportunityInfo() {
        const opp = currentOpportunityData;
        if (!opp) return;
        
        const ownerName = opp.ownerDisplayName || opp.ownerUsername || users.find(u => u.id == opp.owner_user_id)?.display_name || users.find(u => u.id == opp.owner_user_id)?.username || '—';
        
        // Build co-owner display
        let coOwnerHtml = '—';
        if (opp.co_owner_contact_type && opp.coOwnerDisplayName) {
            const typeIcon = opp.co_owner_contact_type === 'user' ? '👤' : opp.co_owner_contact_type === 'federal' ? '🏛️' : '🏢';
            const typeTitle = opp.co_owner_contact_type === 'user' ? 'User' : opp.co_owner_contact_type === 'federal' ? 'Federal Contact' : 'Commercial Contact';
            coOwnerHtml = `<span class="owner-badge" title="${typeTitle}">${typeIcon} ${opp.coOwnerDisplayName}</span>`;
        }
        
        // Build assigned users HTML
        let assignedUsersHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (opp.assignedUsers && opp.assignedUsers.length > 0) {
            assignedUsersHtml = opp.assignedUsers.map(u => `
                <span class="assigned-chip">👤 ${u.display_name || u.username}</span>
            `).join('');
        }
        
        // Build assigned contacts HTML (supports both federal and commercial)
        let assignedContactsHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (opp.assignedContacts && opp.assignedContacts.length > 0) {
            assignedContactsHtml = opp.assignedContacts.map(c => {
                const isFederal = c.contact_type === 'federal' || !c.contact_type;
                const icon = isFederal ? '🏛️' : '🏢';
                const clickHandler = isFederal 
                    ? `closeOpportunityPanel(); setTimeout(() => openContactPanel(${c.contact_id}), 300);`
                    : `closeOpportunityPanel(); setTimeout(() => openCompanyContactPanel(${c.contact_id}), 300);`;
                return `
                <span class="assigned-chip" style="cursor: pointer;" onclick="${clickHandler}">
                    ${icon} ${c.firstName} ${c.lastName}
                </span>
            `}).join('');
        }
        
        const html = `
            <div class="contact-info-row">
                <div class="contact-info-label">Title</div>
                <div class="contact-info-value">${opp.title || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Agency</div>
                <div class="contact-info-value">${opp.agencyName || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Division</div>
                <div class="contact-info-value">${opp.division || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Owner</div>
                <div class="contact-info-value"><span class="owner-badge">👤 ${ownerName}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Co-Owner</div>
                <div class="contact-info-value">${coOwnerHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Value</div>
                <div class="contact-info-value">$${parseFloat(opp.value || 0).toLocaleString()}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Status</div>
                <div class="contact-info-value"><span class="status-badge status-${(opp.status || '').toLowerCase().replace(' ', '')}">${opp.status || '-'}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Due Date</div>
                <div class="contact-info-value">${opp.dueDate || '<span style="color: #6c757d; font-style: italic;">TBD</span>'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Priority</div>
                <div class="contact-info-value">${opp.priority || '-'}</div>
            </div>
            ${opp.description ? `
            <div class="contact-info-row" style="flex-direction: column;">
                <div class="contact-info-label" style="margin-bottom: 8px;">Description</div>
                <div class="contact-info-value" style="white-space: pre-wrap;">${opp.description}</div>
            </div>
            ` : ''}
            
            <div class="assignment-section">
                <h5>👥 Assigned Users</h5>
                <div class="assigned-list">${assignedUsersHtml}</div>
                ${!window.isSpecialty ? `<button class="btn btn-secondary" style="margin-top: 10px; font-size: 0.85rem; padding: 6px 12px;" onclick="openAssignUsersModal()">+ Add User</button>` : ''}
            </div>
            
            <div class="assignment-section">
                <h5>📇 Assigned Contacts</h5>
                <div class="assigned-list">${assignedContactsHtml}</div>
                ${!window.isSpecialty ? `<button class="btn btn-secondary" style="margin-top: 10px; font-size: 0.85rem; padding: 6px 12px;" onclick="openAssignContactsModal()">+ Add Contact</button>` : ''}
            </div>
            
            <div class="assignment-section">
                <h5 style="display: flex; justify-content: space-between; align-items: center;">
                    📎 Documents
                    <label class="btn btn-secondary" style="font-size: 0.85rem; padding: 6px 12px; cursor: pointer; margin: 0;">
                        + Upload
                        <input type="file" id="oppPanelDocumentUpload" style="display: none;" 
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif"
                            onchange="uploadOppPanelDocument()">
                    </label>
                </h5>
                <div id="oppPanelDocumentsList" style="margin-top: 10px; max-height: 250px; overflow-y: auto;">
                    <p style="color: #6c757d; font-style: italic; padding: 10px;">Loading documents...</p>
                </div>
            </div>
            
            <div style="margin-top: 25px; display: flex; gap: 10px;">
                ${!window.isSpecialty ? `<button class="btn" onclick="closeOpportunityPanel(); editItem('opportunity', ${opp.id})">✏️ Edit Opportunity</button>` : ''}
            </div>
        `;
        
        document.getElementById('oppInfoSection').innerHTML = html;
        
        // Load documents for the panel
        loadOppPanelDocuments(opp.id);
    }
    
    // =============================================
    // OPPORTUNITY NOTES FUNCTIONS
    // =============================================
    
    function populateOppNoteFilters() {
        const userSelect = document.getElementById('oppNoteFilterUser');
        userSelect.innerHTML = '<option value="">All Users</option>' + 
            users.map(u => `<option value="${u.id}">${u.display_name || u.username}</option>`).join('');
        
        document.getElementById('oppNoteFilterDateFrom').value = '';
        document.getElementById('oppNoteFilterDateTo').value = '';
        document.getElementById('oppNoteFilterType').value = '';
    }
    
    async function loadOpportunityNotes() {
        if (!currentOpportunityId) return;
        
        try {
            const params = new URLSearchParams({
                action: 'getOpportunityNotes',
                opportunity_id: currentOpportunityId
            });
            
            const dateFrom = document.getElementById('oppNoteFilterDateFrom').value;
            const dateTo = document.getElementById('oppNoteFilterDateTo').value;
            const userId = document.getElementById('oppNoteFilterUser').value;
            const interactionType = document.getElementById('oppNoteFilterType').value;

            if (dateFrom) params.append('filter_date_from', dateFrom);
            if (dateTo) params.append('filter_date_to', dateTo);
            if (userId) params.append('filter_user_id', userId);
            if (interactionType) params.append('filter_interaction_type', interactionType);
            
            const response = await fetch(`${API_URL}?${params.toString()}`);
            const data = await response.json();
            
            if (data.success) {
                currentOpportunityNotes = data.notes || [];
                renderOpportunityNotes();
            }
        } catch (error) {
            console.error('Error loading opportunity notes:', error);
        }
    }
    
    function filterOpportunityNotes() {
        loadOpportunityNotes();
    }
    
    function renderOpportunityNotes() {
        const container = document.getElementById('oppNotesList');
        
        if (currentOpportunityNotes.length === 0) {
            container.innerHTML = `
                <div class="no-notes">
                    <div class="no-notes-icon">📝</div>
                    <p>No notes found</p>
                    <p style="font-size: 0.85rem;">Click "Add Note" to create the first interaction note.</p>
                </div>
            `;
            return;
        }
        
        const html = currentOpportunityNotes.map(note => `
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-meta">
                        <span class="note-user">${note.createdByUsername || 'Unknown'}</span>
                        <span class="note-date">${note.displayDate}</span>
                        ${note.note_date ? `<span class="note-date-badge">${new Date(note.note_date + 'T00:00:00').toLocaleDateString()}</span>` : ''}
                        ${note.interaction_type ? `<span class="note-type">${note.interaction_type}</span>` : ''}
                    </div>
                    ${note.canEdit || note.canDelete ? `
                    <div class="note-actions">
                        ${note.canEdit ? `<button class="note-action-btn edit" onclick="editOpportunityNote(${note.id})" title="Edit">✏️</button>` : ''}
                        ${note.canDelete ? `<button class="note-action-btn delete" onclick="deleteOpportunityNote(${note.id})" title="Delete">🗑️</button>` : ''}
                    </div>
                    ` : ''}
                </div>
                <div class="note-text">${escapeHtml(note.note_text)}</div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }
    
    function openAddOppNoteModal() {
        editingOppNoteId = null;
        document.getElementById('oppNoteModalTitle').textContent = 'Add Note';
        document.getElementById('oppNoteId').value = '';
        document.getElementById('oppNoteOpportunityId').value = currentOpportunityId;
        document.getElementById('oppNoteDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('oppNoteInteractionType').value = '';
        document.getElementById('oppNoteText').value = '';

        document.getElementById('oppNoteModal').style.display = 'block';
    }
    
    function editOpportunityNote(noteId) {
        const note = currentOpportunityNotes.find(n => n.id == noteId);
        if (!note) return;
        
        editingOppNoteId = noteId;
        document.getElementById('oppNoteModalTitle').textContent = 'Edit Note';
        document.getElementById('oppNoteId').value = noteId;
        document.getElementById('oppNoteOpportunityId').value = currentOpportunityId;
        document.getElementById('oppNoteInteractionType').value = note.interaction_type || '';
        document.getElementById('oppNoteText').value = note.note_text || '';
        document.getElementById('oppNoteDate').value = note.note_date || '';

        document.getElementById('oppNoteModal').style.display = 'block';
    }
    
    async function saveOpportunityNote(event) {
        event.preventDefault();
        
        const noteData = {
            id: document.getElementById('oppNoteId').value || null,
            opportunity_id: document.getElementById('oppNoteOpportunityId').value,
            note_date: document.getElementById('oppNoteDate').value || null,
            interaction_type: document.getElementById('oppNoteInteractionType').value,
            note_text: document.getElementById('oppNoteText').value
        };
        
        if (!noteData.note_text.trim()) {
            showToast('Please enter a note.', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityNote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                closeOppNoteModal();
                loadOpportunityNotes();
            } else {
                showToast('Error saving note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving note:', error);
            showToast('Error saving note', 'error');
        }
    }
    
    async function deleteOpportunityNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteOpportunityNote&id=${noteId}`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadOpportunityNotes();
            } else {
                showToast('Error deleting note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showToast('Error deleting note', 'error');
        }
    }
    
    function closeOppNoteModal() {
        document.getElementById('oppNoteModal').style.display = 'none';
        editingOppNoteId = null;
    }
    
    // =============================================
    // OPPORTUNITY ASSIGNMENTS (Users & Contacts)
    // =============================================
    
    function openAssignUsersModal() {
        const assignedUsers = currentOpportunityData?.assignedUsers || [];
        
        const assignedListHtml = assignedUsers.length > 0 
            ? assignedUsers.map(u => `
                <div class="assigned-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px;">
                    <span>👤 ${u.display_name || u.username}</span>
                    <button class="action-btn delete" onclick="removeAssignedUser(${u.user_id})" title="Remove" style="padding: 4px 8px;">✕</button>
                </div>
            `).join('')
            : '<p style="color: #6c757d; font-style: italic;">No users assigned yet.</p>';
        
        const html = `
            <div class="modal-content" style="max-width: 450px;">
                <span class="close" onclick="document.getElementById('assignModal').style.display='none'">&times;</span>
                <h2>Add User to Opportunity</h2>
                
                <div class="form-group" style="margin-bottom: 15px; position: relative;">
                    <label>Search User *</label>
                    <input type="text" id="assignUserSearch" placeholder="Type to search users..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;"
                        oninput="filterAssignUserDropdown()" onfocus="showAssignUserDropdown()">
                    <div id="assignUserDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 1000;">
                        ${users.map(u => `
                            <div class="dropdown-item" data-id="${u.id}" data-name="${u.display_name || u.username}" 
                                onclick="selectAssignUser(${u.id}, '${(u.display_name || u.username).replace(/'/g, "\\'")}')"
                                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                                👤 ${u.display_name || u.username}
                            </div>
                        `).join('')}
                    </div>
                    <input type="hidden" id="assignUserSelectedId">
                </div>
                
                <button class="btn" onclick="addAssignedUser()" style="width: 100%;">Add User</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">👥 Currently Assigned Users</h4>
                    <div id="currentAssignedUsers" style="max-height: 200px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('assignModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'assignModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        
        // Close dropdown when clicking outside
        document.addEventListener('click', closeAssignDropdownOnOutsideClick);
    }
    
    function openAssignContactsModal() {
        const assignedContacts = currentOpportunityData?.assignedContacts || [];
        
        const assignedListHtml = assignedContacts.length > 0 
            ? assignedContacts.map(c => {
                const isFederal = c.contact_type === 'federal' || !c.contact_type;
                const icon = isFederal ? '🏛️' : '🏢';
                const subtext = isFederal ? (c.agencyName || 'No Agency') : (c.companyName || 'No Company');
                return `
                <div class="assigned-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px;">
                    <div>
                        <span>${icon} ${c.firstName} ${c.lastName}</span>
                        <span style="color: #6c757d; font-size: 0.85rem;"> - ${subtext}</span>
                    </div>
                    <button class="action-btn delete" onclick="removeAssignedContact(${c.contact_id}, '${c.contact_type || 'federal'}')" title="Remove" style="padding: 4px 8px;">✕</button>
                </div>
            `}).join('')
            : '<p style="color: #6c757d; font-style: italic;">No contacts assigned yet.</p>';
        
        // Build combined contact list (federal + commercial)
        const federalContactsHtml = contacts.filter(c => c.status === 'Active').map(c => `
            <div class="dropdown-item" data-id="${c.id}" data-type="federal" data-name="${c.firstName} ${c.lastName}" 
                onclick="selectAssignContact(${c.id}, 'federal', '${(c.firstName + ' ' + c.lastName).replace(/'/g, "\\'")}')"
                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                🏛️ ${c.firstName} ${c.lastName} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.agencyName || 'No Agency'}</span>
            </div>
        `).join('');
        
        const commercialContactsHtml = companyContacts.filter(c => c.status === 'Active').map(c => `
            <div class="dropdown-item" data-id="${c.id}" data-type="commercial" data-name="${c.first_name} ${c.last_name}" 
                onclick="selectAssignContact(${c.id}, 'commercial', '${(c.first_name + ' ' + c.last_name).replace(/'/g, "\\'")}')"
                style="padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                🏢 ${c.first_name} ${c.last_name} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.companyName || 'No Company'}</span>
            </div>
        `).join('');
        
        const html = `
            <div class="modal-content" style="max-width: 450px;">
                <span class="close" onclick="document.getElementById('assignModal').style.display='none'">&times;</span>
                <h2>Add Contact to Opportunity</h2>
                
                <div class="form-group" style="margin-bottom: 15px; position: relative;">
                    <label>Search Contact * <span style="font-size: 0.8rem; color: #6c757d;">(🏛️ Federal / 🏢 Commercial)</span></label>
                    <input type="text" id="assignContactSearch" placeholder="Type to search contacts..." 
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;"
                        oninput="filterAssignContactDropdown()" onfocus="showAssignContactDropdown()">
                    <div id="assignContactDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 300px; overflow-y: auto; background: white; border: 2px solid #e1e5e9; border-top: none; border-radius: 0 0 8px 8px; z-index: 1000;">
                        ${federalContactsHtml}${commercialContactsHtml || ''}
                        ${!federalContactsHtml && !commercialContactsHtml ? '<div style="padding: 10px 15px; color: #6c757d; font-style: italic;">No active contacts</div>' : ''}
                    </div>
                    <input type="hidden" id="assignContactSelectedId">
                    <input type="hidden" id="assignContactSelectedType">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Role (optional)</label>
                    <input type="text" id="assignContactRole" placeholder="e.g., Decision Maker, Technical Contact..."
                        style="width: 100%; box-sizing: border-box; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px;">
                </div>
                
                <button class="btn" onclick="addAssignedContact()" style="width: 100%;">Add Contact</button>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <h4 style="margin: 0 0 15px 0; color: #495057;">📇 Currently Assigned Contacts</h4>
                    <div id="currentAssignedContacts" style="max-height: 200px; overflow-y: auto;">
                        ${assignedListHtml}
                    </div>
                </div>
            </div>
        `;
        
        let modal = document.getElementById('assignModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'assignModal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        modal.innerHTML = html;
        modal.style.display = 'block';
        
        // Close dropdown when clicking outside
        document.addEventListener('click', closeAssignDropdownOnOutsideClick);
    }
    
    function closeAssignDropdownOnOutsideClick(e) {
        const userDropdown = document.getElementById('assignUserDropdown');
        const contactDropdown = document.getElementById('assignContactDropdown');
        const userSearch = document.getElementById('assignUserSearch');
        const contactSearch = document.getElementById('assignContactSearch');
        
        if (userDropdown && userSearch && !userDropdown.contains(e.target) && e.target !== userSearch) {
            userDropdown.style.display = 'none';
        }
        if (contactDropdown && contactSearch && !contactDropdown.contains(e.target) && e.target !== contactSearch) {
            contactDropdown.style.display = 'none';
        }
    }
    
    // User dropdown functions
    function showAssignUserDropdown() {
        const dropdown = document.getElementById('assignUserDropdown');
        if (dropdown) dropdown.style.display = 'block';
    }
    
    function filterAssignUserDropdown() {
        const searchTerm = document.getElementById('assignUserSearch').value.toLowerCase();
        const dropdown = document.getElementById('assignUserDropdown');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function selectAssignUser(userId, userName) {
        document.getElementById('assignUserSearch').value = userName;
        document.getElementById('assignUserSelectedId').value = userId;
        document.getElementById('assignUserDropdown').style.display = 'none';
    }
    
    // Contact dropdown functions
    function showAssignContactDropdown() {
        const dropdown = document.getElementById('assignContactDropdown');
        if (dropdown) dropdown.style.display = 'block';
    }
    
    function filterAssignContactDropdown() {
        const searchTerm = document.getElementById('assignContactSearch').value.toLowerCase();
        const dropdown = document.getElementById('assignContactDropdown');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.dropdown-item').forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    function selectAssignContact(contactId, contactType, contactName) {
        document.getElementById('assignContactSearch').value = contactName;
        document.getElementById('assignContactSelectedId').value = contactId;
        document.getElementById('assignContactSelectedType').value = contactType;
        document.getElementById('assignContactDropdown').style.display = 'none';
    }
    
    // Add/Remove functions
    async function addAssignedUser() {
        const userId = document.getElementById('assignUserSelectedId').value;
        if (!userId) {
            showToast('Please select a user to add.', 'warning');
            return;
        }
        
        // Check if already assigned
        const assignedUsers = currentOpportunityData?.assignedUsers || [];
        if (assignedUsers.some(u => u.user_id == userId)) {
            showToast('This user is already assigned to the opportunity.', 'info');
            return;
        }
        
        const newUserIds = [...assignedUsers.map(u => u.user_id), parseInt(userId)];
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    user_ids: newUserIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadOpportunityDetails(currentOpportunityId);
                // Refresh the modal
                openAssignUsersModal();
            } else {
                showToast('Error adding user: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error adding user:', error);
            showToast('Error adding user', 'error');
        }
    }
    
    async function removeAssignedUser(userId) {
        if (!confirm('Remove this user from the opportunity?')) return;
        
        const assignedUsers = currentOpportunityData?.assignedUsers || [];
        const newUserIds = assignedUsers.filter(u => u.user_id != userId).map(u => u.user_id);
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    user_ids: newUserIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadOpportunityDetails(currentOpportunityId);
                // Refresh the modal
                openAssignUsersModal();
            } else {
                showToast('Error removing user: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error removing user:', error);
            showToast('Error removing user', 'error');
        }
    }
    
    async function addAssignedContact() {
        const contactId = document.getElementById('assignContactSelectedId').value;
        const contactType = document.getElementById('assignContactSelectedType').value || 'federal';
        
        if (!contactId) {
            showToast('Please select a contact to add.', 'warning');
            return;
        }
        
        // Check if already assigned (check by id AND type to avoid confusion)
        const assignedContacts = currentOpportunityData?.assignedContacts || [];
        if (assignedContacts.some(c => c.contact_id == contactId && (c.contact_type || 'federal') === contactType)) {
            showToast('This contact is already assigned to the opportunity.', 'info');
            return;
        }
        
        // Build contacts array with type information
        const newContacts = [
            ...assignedContacts.map(c => ({ id: c.contact_id, type: c.contact_type || 'federal' })),
            { id: parseInt(contactId), type: contactType }
        ];
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    contacts: newContacts
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadOpportunityDetails(currentOpportunityId);
                // Refresh the modal
                openAssignContactsModal();
            } else {
                showToast('Error adding contact: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error adding contact:', error);
            showToast('Error adding contact', 'error');
        }
    }
    
    async function removeAssignedContact(contactId, contactType) {
        if (!confirm('Remove this contact from the opportunity?')) return;
        
        const assignedContacts = currentOpportunityData?.assignedContacts || [];
        // Filter out the specific contact by id AND type
        const newContacts = assignedContacts
            .filter(c => !(c.contact_id == contactId && (c.contact_type || 'federal') === contactType))
            .map(c => ({ id: c.contact_id, type: c.contact_type || 'federal' }));
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    contacts: newContacts
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await loadOpportunityDetails(currentOpportunityId);
                // Refresh the modal
                openAssignContactsModal();
            } else {
                showToast('Error removing contact: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error removing contact:', error);
            showToast('Error removing contact', 'error');
        }
    }
    
    function filterAssignContacts() {
        const searchTerm = document.getElementById('contactSearchAssign').value.toLowerCase();
        document.querySelectorAll('.assign-contact-item').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    async function saveAssignedUsers() {
        const userIds = Array.from(document.querySelectorAll('.assign-user-checkbox:checked')).map(cb => parseInt(cb.value));
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    user_ids: userIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('assignModal').style.display = 'none';
                await loadOpportunityDetails(currentOpportunityId);
            } else {
                showToast('Error saving assignments: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showToast('Error saving assignments', 'error');
        }
    }
    
    async function saveAssignedContacts() {
        const contactIds = Array.from(document.querySelectorAll('.assign-contact-checkbox:checked')).map(cb => parseInt(cb.value));
        
        try {
            const response = await fetch(`${API_URL}?action=saveOpportunityAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    opportunity_id: currentOpportunityId,
                    contact_ids: contactIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('assignModal').style.display = 'none';
                await loadOpportunityDetails(currentOpportunityId);
            } else {
                showToast('Error saving assignments: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showToast('Error saving assignments', 'error');
        }
    }
    
    // =============================================
    // PROPOSAL PANEL FUNCTIONS
    // =============================================
    
    async function openProposalPanel(propId) {
        currentProposalId = propId;
        const prop = proposals.find(p => p.id == propId);
        if (!prop) return;
        
        // Set header info
        document.getElementById('propPanelTitle').textContent = prop.title || 'Untitled Proposal';
        document.getElementById('propPanelSubtitle').textContent = `${prop.agencyName || 'No Agency'} - ${prop.status || ''}`;
        
        // Load full proposal details including assigned users/contacts
        await loadProposalDetails(propId);
        
        // Populate note filter dropdowns
        populatePropNoteFilters();
        
        // Load notes
        loadProposalNotes();
        
        // Switch to info tab by default
        switchPropTab('info');
        
        // Show panel
        document.getElementById('propPanelOverlay').classList.add('open');
        document.getElementById('propPanel').classList.add('open');
    }
    
    function openProposalNotes(propId) {
        currentProposalId = propId;
        const prop = proposals.find(p => p.id == propId);
        if (!prop) return;
        
        // Set header info
        document.getElementById('propPanelTitle').textContent = prop.title || 'Untitled Proposal';
        document.getElementById('propPanelSubtitle').textContent = `${prop.agencyName || 'No Agency'} - ${prop.status || ''}`;
        
        // Load full proposal details
        loadProposalDetails(propId);
        
        // Populate note filter dropdowns
        populatePropNoteFilters();
        
        // Load notes
        loadProposalNotes();
        
        // Switch to NOTES tab directly
        switchPropTab('notes');
        
        // Show panel
        document.getElementById('propPanelOverlay').classList.add('open');
        document.getElementById('propPanel').classList.add('open');
    }
    
    function closeProposalPanel() {
        document.getElementById('propPanelOverlay').classList.remove('open');
        document.getElementById('propPanel').classList.remove('open');
        currentProposalId = null;
        currentProposalData = null;
        currentProposalNotes = [];
    }
    
    function switchPropTab(tab) {
        document.querySelectorAll('#propPanel .contact-panel-tab').forEach(t => {
            t.classList.remove('active');
            if ((tab === 'info' && t.textContent === 'Info') || (tab === 'notes' && t.textContent === 'Notes')) {
                t.classList.add('active');
            }
        });
        
        if (tab === 'info') {
            document.getElementById('propInfoSection').style.display = 'block';
            document.getElementById('propNotesSection').style.display = 'none';
        } else {
            document.getElementById('propInfoSection').style.display = 'none';
            document.getElementById('propNotesSection').style.display = 'block';
        }
    }
    
    async function loadProposalDetails(propId) {
        try {
            const response = await fetch(`${API_URL}?action=getProposalDetails&id=${propId}`);
            const data = await response.json();
            
            if (data.success) {
                currentProposalData = data.proposal;
                renderProposalInfo();
            }
        } catch (error) {
            console.error('Error loading proposal details:', error);
        }
    }
    
    function renderProposalInfo() {
        const prop = currentProposalData;
        if (!prop) return;
        
        const ownerName = prop.ownerDisplayName || prop.ownerUsername || users.find(u => u.id == prop.owner_user_id)?.display_name || users.find(u => u.id == prop.owner_user_id)?.username || '—';
        
        // Build assigned users HTML - show ALL users
        let assignedUsersHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (prop.assignedUsers && prop.assignedUsers.length > 0) {
            assignedUsersHtml = prop.assignedUsers.map(u => `
                <span class="assigned-chip">👤 ${u.display_name || u.username}</span>
            `).join('');
        }
        
        // Build assigned contacts HTML - show ALL contacts
        let assignedContactsHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (prop.assignedContacts && prop.assignedContacts.length > 0) {
            assignedContactsHtml = prop.assignedContacts.map(c => `
                <span class="assigned-chip" style="cursor: pointer;" onclick="closeProposalPanel(); setTimeout(() => openContactPanel(${c.contact_id}), 300);">
                    👤 ${c.firstName} ${c.lastName}
                </span>
            `).join('');
        }
        
        const html = `
            <div class="contact-info-row">
                <div class="contact-info-label">Title</div>
                <div class="contact-info-value">${prop.title || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Agency</div>
                <div class="contact-info-value">${prop.agencyName || '-'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Owner</div>
                <div class="contact-info-value"><span class="owner-badge">👤 ${ownerName}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Value</div>
                <div class="contact-info-value">$${parseFloat(prop.value || 0).toLocaleString()}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Status</div>
                <div class="contact-info-value"><span class="status-badge status-${(prop.status || '').toLowerCase().replace(' ', '')}">${prop.status || '-'}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Submit Date</div>
                <div class="contact-info-value" style="color: #28a745;">${prop.submitDate || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Due Date</div>
                <div class="contact-info-value" style="color: #dc3545;">${prop.dueDate || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Win Probability</div>
                <div class="contact-info-value">${prop.winProbability || 0}%</div>
            </div>
            ${prop.description ? `
            <div class="contact-info-row" style="flex-direction: column;">
                <div class="contact-info-label" style="margin-bottom: 8px;">Description</div>
                <div class="contact-info-value" style="white-space: pre-wrap;">${prop.description}</div>
            </div>
            ` : ''}
            
            <div class="assignment-section">
                <h5>👥 Assigned Users</h5>
                <div class="assigned-list">${assignedUsersHtml}</div>
                ${!window.isSpecialty ? `<button class="btn btn-secondary" style="margin-top: 10px; font-size: 0.85rem; padding: 6px 12px;" onclick="openAssignPropUsersModal()">Manage Users</button>` : ''}
            </div>
            
            <div class="assignment-section">
                <h5>📇 Assigned Contacts</h5>
                <div class="assigned-list">${assignedContactsHtml}</div>
                ${!window.isSpecialty ? `<button class="btn btn-secondary" style="margin-top: 10px; font-size: 0.85rem; padding: 6px 12px;" onclick="openAssignPropContactsModal()">Manage Contacts</button>` : ''}
            </div>
            
            <div class="assignment-section">
                <h5 style="display: flex; justify-content: space-between; align-items: center;">
                    📎 Documents
                    <label class="btn btn-secondary" style="font-size: 0.85rem; padding: 6px 12px; cursor: pointer; margin: 0;">
                        + Upload
                        <input type="file" id="propPanelDocumentUpload" style="display: none;" 
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif"
                            onchange="uploadPropPanelDocument()">
                    </label>
                </h5>
                <div id="propPanelDocumentsList" style="margin-top: 10px; max-height: 250px; overflow-y: auto;">
                    <p style="color: #6c757d; font-style: italic; padding: 10px;">Loading documents...</p>
                </div>
            </div>
            
            <div style="margin-top: 25px; display: flex; gap: 10px;">
                ${!window.isSpecialty ? `<button class="btn" onclick="closeProposalPanel(); editItem('proposal', ${prop.id})">✏️ Edit Proposal</button>` : ''}
            </div>
        `;
        
        document.getElementById('propInfoSection').innerHTML = html;
        
        // Load documents for the panel
        loadPropPanelDocuments(prop.id);
    }
    
    // =============================================
    // PROPOSAL NOTES FUNCTIONS
    // =============================================
    
    function populatePropNoteFilters() {
        const userSelect = document.getElementById('propNoteFilterUser');
        userSelect.innerHTML = '<option value="">All Users</option>' + 
            users.map(u => `<option value="${u.id}">${u.display_name || u.username}</option>`).join('');
        
        document.getElementById('propNoteFilterDateFrom').value = '';
        document.getElementById('propNoteFilterDateTo').value = '';
        document.getElementById('propNoteFilterType').value = '';
    }
    
    async function loadProposalNotes() {
        if (!currentProposalId) return;
        
        let url = `${API_URL}?action=getProposalNotes&proposal_id=${currentProposalId}`;
        
        // Add filters
        const dateFrom = document.getElementById('propNoteFilterDateFrom')?.value;
        const dateTo = document.getElementById('propNoteFilterDateTo')?.value;
        const userId = document.getElementById('propNoteFilterUser')?.value;
        const interactionType = document.getElementById('propNoteFilterType')?.value;

        if (dateFrom) url += `&filter_date_from=${dateFrom}`;
        if (dateTo) url += `&filter_date_to=${dateTo}`;
        if (userId) url += `&filter_user_id=${userId}`;
        if (interactionType) url += `&filter_interaction_type=${encodeURIComponent(interactionType)}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                currentProposalNotes = data.notes;
                renderProposalNotes();
            }
        } catch (error) {
            console.error('Error loading proposal notes:', error);
        }
    }
    
    function filterProposalNotes() {
        loadProposalNotes();
    }
    
    function renderProposalNotes() {
        const container = document.getElementById('propNotesList');
        if (!container) return;
        
        if (currentProposalNotes.length === 0) {
            container.innerHTML = '<div class="empty-notes">No notes found. Add a note to track interactions.</div>';
            return;
        }
        
        container.innerHTML = currentProposalNotes.map(note => `
            <div class="note-card">
                <div class="note-header">
                    <div class="note-meta">
                        <span class="note-user">${note.createdByUsername || 'Unknown'}</span>
                        ${note.note_date ? `<span class="note-date-badge">${new Date(note.note_date + 'T00:00:00').toLocaleDateString()}</span>` : ''}
                        ${note.interaction_type ? `<span class="note-type">${note.interaction_type}</span>` : ''}
                    </div>
                    <div class="note-date">${note.displayDate}</div>
                </div>
                <div class="note-text">${(note.note_text || '').replace(/\n/g, '<br>')}</div>
                ${note.canEdit || note.canDelete ? `
                <div class="note-actions">
                    ${note.canEdit ? `<button class="note-action-btn" onclick="editProposalNote(${note.id})">Edit</button>` : ''}
                    ${note.canDelete ? `<button class="note-action-btn delete" onclick="deleteProposalNote(${note.id})">Delete</button>` : ''}
                </div>
                ` : ''}
            </div>
        `).join('');
    }
    
    function openAddPropNoteModal() {
        document.getElementById('propNoteModalTitle').textContent = 'Add Note';
        document.getElementById('propNoteId').value = '';
        document.getElementById('propNoteProposalId').value = currentProposalId;
        document.getElementById('propNoteDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('propNoteInteractionType').value = '';
        document.getElementById('propNoteText').value = '';
        editingPropNoteId = null;
        document.getElementById('propNoteModal').style.display = 'block';
    }
    
    function editProposalNote(noteId) {
        const note = currentProposalNotes.find(n => n.id == noteId);
        if (!note) return;
        
        document.getElementById('propNoteModalTitle').textContent = 'Edit Note';
        document.getElementById('propNoteId').value = note.id;
        document.getElementById('propNoteProposalId').value = currentProposalId;
        document.getElementById('propNoteDate').value = note.note_date || '';
        document.getElementById('propNoteInteractionType').value = note.interaction_type || '';
        document.getElementById('propNoteText').value = note.note_text || '';
        editingPropNoteId = noteId;
        document.getElementById('propNoteModal').style.display = 'block';
    }
    
    async function saveProposalNote(event) {
        event.preventDefault();
        
        const noteData = {
            id: document.getElementById('propNoteId').value || null,
            proposal_id: currentProposalId,
            note_date: document.getElementById('propNoteDate').value || null,
            interaction_type: document.getElementById('propNoteInteractionType').value,
            note_text: document.getElementById('propNoteText').value
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveProposalNote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                closePropNoteModal();
                loadProposalNotes();
            } else {
                showToast('Error saving note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving note:', error);
            showToast('Error saving note', 'error');
        }
    }
    
    async function deleteProposalNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteProposalNote&id=${noteId}`, {
                method: 'POST'
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadProposalNotes();
            } else {
                showToast('Error deleting note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showToast('Error deleting note', 'error');
        }
    }
    
    function closePropNoteModal() {
        document.getElementById('propNoteModal').style.display = 'none';
        editingPropNoteId = null;
    }
    
    // =============================================
    // PROPOSAL ASSIGNMENTS (Users & Contacts)
    // =============================================
    
    function openAssignPropUsersModal() {
        const assignedUserIds = (currentProposalData?.assignedUsers || []).map(u => u.user_id);
        
        const checkboxList = document.getElementById('propUserCheckboxList');
        checkboxList.innerHTML = users.map(u => `
            <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                <input type="checkbox" class="assign-prop-user-checkbox" value="${u.id}" ${assignedUserIds.includes(u.id) ? 'checked' : ''} style="margin-right: 10px;">
                <span>👤 ${u.display_name || u.username}</span>
            </label>
        `).join('');
        
        document.getElementById('assignPropUsersModal').style.display = 'block';
    }
    
    function closeAssignPropUsersModal() {
        document.getElementById('assignPropUsersModal').style.display = 'none';
    }
    
    function openAssignPropContactsModal() {
        const assignedContactIds = (currentProposalData?.assignedContacts || []).map(c => c.contact_id);
        
        const checkboxList = document.getElementById('propContactCheckboxList');
        checkboxList.innerHTML = `
            <input type="text" id="propContactSearchAssign" placeholder="Search contacts..." style="width: 100%; box-sizing: border-box; padding: 10px; margin-bottom: 10px; border: 2px solid #e1e5e9; border-radius: 8px;" oninput="filterAssignPropContacts()">
            <div id="propContactItems">
                ${contacts.filter(c => c.status === 'Active').map(c => `
                    <label class="assign-prop-contact-item" data-name="${(c.firstName + ' ' + c.lastName).toLowerCase()}" style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                        <input type="checkbox" class="assign-prop-contact-checkbox" value="${c.id}" ${assignedContactIds.includes(c.id) ? 'checked' : ''} style="margin-right: 10px;">
                        <span>👤 ${c.firstName} ${c.lastName} <span style="color: #6c757d; font-size: 0.85rem;">- ${c.agencyName || 'No Agency'}</span></span>
                    </label>
                `).join('')}
            </div>
        `;
        
        document.getElementById('assignPropContactsModal').style.display = 'block';
    }
    
    function closeAssignPropContactsModal() {
        document.getElementById('assignPropContactsModal').style.display = 'none';
    }
    
    function filterAssignPropContacts() {
        const searchTerm = document.getElementById('propContactSearchAssign').value.toLowerCase();
        document.querySelectorAll('.assign-prop-contact-item').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    async function savePropUserAssignments() {
        const userIds = Array.from(document.querySelectorAll('.assign-prop-user-checkbox:checked')).map(cb => parseInt(cb.value));
        
        try {
            const response = await fetch(`${API_URL}?action=saveProposalAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    proposal_id: currentProposalId,
                    user_ids: userIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                closeAssignPropUsersModal();
                await loadProposalDetails(currentProposalId);
            } else {
                showToast('Error saving assignments: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showToast('Error saving assignments', 'error');
        }
    }
    
    async function savePropContactAssignments() {
        const contactIds = Array.from(document.querySelectorAll('.assign-prop-contact-checkbox:checked')).map(cb => parseInt(cb.value));
        
        try {
            const response = await fetch(`${API_URL}?action=saveProposalAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    proposal_id: currentProposalId,
                    contact_ids: contactIds
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                closeAssignPropContactsModal();
                await loadProposalDetails(currentProposalId);
            } else {
                showToast('Error saving assignments: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showToast('Error saving assignments', 'error');
        }
    }

    // ==================== EVENTS ====================
    
    // Event table sorting state
    let eventSortColumn = 'start_datetime';
    let eventSortDirection = 'desc';
    
    // Format event datetime
    function formatEventDateTime(datetime) {
        if (!datetime) return '—';
        const date = new Date(datetime);
        return date.toLocaleString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }
    
    // Format event date only
    function formatEventDate(datetime) {
        if (!datetime) return '—';
        const date = new Date(datetime);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric'
        });
    }
    
    // Populate events table
    function populateEventsTable() {
        const tbody = document.getElementById('eventsTableBody');
        if (!tbody) return;
        
        let filteredEvents = [...events];
        
        // Apply filters
        const searchTerm = (document.getElementById('eventSearch')?.value || '').toLowerCase();
        const statusFilter = document.getElementById('eventStatusFilter')?.value || '';
        const typeFilter = document.getElementById('eventTypeFilter')?.value || '';
        
        filteredEvents = filteredEvents.filter(e => {
            const matchesSearch = !searchTerm || 
                (e.name || '').toLowerCase().includes(searchTerm) ||
                (e.location || '').toLowerCase().includes(searchTerm) ||
                (e.description || '').toLowerCase().includes(searchTerm);
            const matchesStatus = !statusFilter || e.status === statusFilter;
            const matchesType = !typeFilter || e.event_type === typeFilter;
            return matchesSearch && matchesStatus && matchesType;
        });
        
        // Sort events
        filteredEvents.sort((a, b) => {
            let aVal = a[eventSortColumn];
            let bVal = b[eventSortColumn];
            
            // Handle dates
            if (eventSortColumn.includes('datetime')) {
                aVal = aVal ? new Date(aVal).getTime() : 0;
                bVal = bVal ? new Date(bVal).getTime() : 0;
            }
            
            // Handle strings
            if (typeof aVal === 'string') aVal = aVal.toLowerCase();
            if (typeof bVal === 'string') bVal = bVal.toLowerCase();
            
            if (aVal < bVal) return eventSortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return eventSortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        if (filteredEvents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: #6c757d;">No events found</td></tr>';
            return;
        }
        
        let html = '';
        filteredEvents.forEach(e => {
            // Get status badge color
            const statusColors = {
                'Planning': '#17a2b8',
                'Confirmed': '#28a745',
                'In Progress': '#ffc107',
                'Completed': '#6c757d',
                'Cancelled': '#dc3545'
            };
            const statusColor = statusColors[e.status] || '#6c757d';
            
            // Get event type icon
            const typeIcons = {
                'Meeting': '🤝',
                'Conference': '🏛️',
                'Training': '📚',
                'Webinar': '💻',
                'Site Visit': '🏢',
                'Review': '📋',
                'Workshop': '🔧',
                'Other': '📅'
            };
            const typeIcon = typeIcons[e.event_type] || '📅';
            
            // Get owner name
            const ownerName = e.ownerDisplayName || e.ownerUsername || '—';
            
            // Get people count
            const usersCount = (eventUsers[e.id] || []).length;
            const fedContactsCount = (eventFederalContacts[e.id] || []).length;
            const commContactsCount = (eventCommercialContacts[e.id] || []).length;
            const totalPeople = usersCount + fedContactsCount + commContactsCount;
            
            let peopleHtml = '';
            if (totalPeople === 0) {
                peopleHtml = '<span style="color: #6c757d;">—</span>';
            } else {
                peopleHtml = `<div style="display: flex; gap: 3px; flex-wrap: wrap;">`;
                if (usersCount > 0) peopleHtml += `<span class="badge" style="background: #a8b5f7; color: #1a237e; font-size: 0.7rem;" title="CRM Users">👤${usersCount}</span>`;
                if (fedContactsCount > 0) peopleHtml += `<span class="badge" style="background: #81c784; color: #1b5e20; font-size: 0.7rem;" title="Federal">🏛️${fedContactsCount}</span>`;
                if (commContactsCount > 0) peopleHtml += `<span class="badge" style="background: #ffb74d; color: #e65100; font-size: 0.7rem;" title="Commercial">🏢${commContactsCount}</span>`;
                peopleHtml += `</div>`;
            }
            
            // Get tasks for this event
            const eventTasks = tasks.filter(t => {
                const matchesType = (t.relatedTo === 'Event' || t.relatedTo === 'event');
                const matchesId = parseInt(t.related_item_id) === parseInt(e.id);
                return matchesType && matchesId;
            });
            const taskCount = eventTasks.length;
            const isExpanded = expandedEventTasks[e.id] || false;
            
            // Task toggle button
            let taskToggleHtml = '';
            if (taskCount > 0) {
                taskToggleHtml = `<button onclick="toggleEventTasks(${e.id})" class="btn btn-small" style="padding: 2px 8px; font-size: 0.8rem; background: #f0f4ff; color: #667eea; border: 1px solid #667eea;">${isExpanded ? '▼' : '▶'} (${taskCount})</button>`;
            } else {
                taskToggleHtml = `<span style="color: #6c757d;">—</span>`;
            }
            
            // Actions
            const canArchive = !window.isSpecialty && userPermissions.event?.can_archive;
            const actions = getEventActionsHtml(e, canArchive);
            
            // Main event row
            html += `<tr data-event-id="${e.id}" style="${isExpanded ? 'background: #f8f9ff;' : ''}">
                <td style="width: 30px;">${taskToggleHtml}</td>
                <td><span class="clickable-name" onclick="viewEvent(${e.id})">${escapeHtml(e.name || '')}</span></td>
                <td><span title="${e.event_type}">${typeIcon}</span></td>
                <td>${formatEventDateTime(e.start_datetime)}</td>
                <td><span class="status-badge" style="background: ${statusColor}; color: white;">${e.status}</span></td>
                <td>${escapeHtml(ownerName)}</td>
                <td>${peopleHtml}</td>
                <td>
                    ${!window.isSpecialty ? `<button class="btn btn-small" style="background: #28a745; font-size: 0.75rem; padding: 3px 8px;" onclick="openAddTaskForEvent(${e.id}, '${escapeHtml(e.name || '')}')" title="Add Task">+ 📋</button>` : `<span style="color: #6c757d;">—</span>`}
                </td>
                <td>${actions}</td>
            </tr>`;
            
            // Collapsible task rows
            if (isExpanded && eventTasks.length > 0) {
                const statusColors = { 'To Do': '#17a2b8', 'In Progress': '#ffc107', 'Done': '#28a745' };
                const priorityColors = { 'Low': '#28a745', 'Medium': '#ffc107', 'High': '#dc3545' };
                
                html += `<tr class="event-tasks-row" data-event-id="${e.id}">
                    <td colspan="10" style="padding: 0;">
                        <div style="background: #fafbfc; padding: 10px 15px; margin: 0 15px 10px 40px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <table style="width: 100%; font-size: 0.9rem;">
                                <thead style="background: #e9ecef;">
                                    <tr>
                                        <th style="padding: 5px 10px; text-align: left;">Task</th>
                                        <th style="padding: 5px 10px; text-align: center; width: 80px;">Status</th>
                                        <th style="padding: 5px 10px; text-align: center; width: 80px;">Priority</th>
                                        <th style="padding: 5px 10px; text-align: center; width: 100px;">Due Date</th>
                                        <th style="padding: 5px 10px; text-align: left; width: 120px;">Assignee</th>
                                        <th style="padding: 5px 10px; text-align: center; width: 80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${eventTasks.map(t => `
                                        <tr style="border-bottom: 1px solid #e9ecef;">
                                            <td style="padding: 8px 10px;">
                                                <span class="clickable-name" onclick="editItem('task', ${t.id})">${escapeHtml(t.title)}</span>
                                                ${t.description ? `<div style="font-size: 0.8rem; color: #6c757d; margin-top: 3px; white-space: pre-wrap; max-height: 40px; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(t.description)}</div>` : ''}
                                            </td>
                                            <td style="padding: 8px 10px; text-align: center;"><span class="badge" style="background: ${statusColors[t.status] || '#6c757d'}; font-size: 0.75rem;">${t.status}</span></td>
                                            <td style="padding: 8px 10px; text-align: center;"><span class="badge" style="background: ${priorityColors[t.priority] || '#ffc107'}; font-size: 0.75rem;">${t.priority}</span></td>
                                            <td style="padding: 8px 10px; text-align: center;">${t.dueDate || '—'}</td>
                                            <td style="padding: 8px 10px;">${escapeHtml(t.assignedToDisplayName || '—')}</td>
                                            <td style="padding: 8px 10px; text-align: center;">
                                                ${!window.isSpecialty ? `
                                                    <button class="btn btn-edit btn-small" onclick="editItem('task', ${t.id})" title="Edit" style="padding: 2px 6px;">✏️</button>
                                                    <button class="btn btn-delete btn-small" onclick="unlinkTaskFromEvent(${t.id}, ${e.id})" title="Unlink" style="padding: 2px 6px;">🔗</button>
                                                ` : ''}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>`;
            }
        });
        
        tbody.innerHTML = html;
        
        // Update sort indicators
        document.querySelectorAll('#eventsTable .sort-indicator').forEach(ind => {
            const col = ind.dataset.col;
            if (col === eventSortColumn) {
                ind.textContent = eventSortDirection === 'asc' ? '▲' : '▼';
                ind.parentElement.classList.add('sorted');
            } else {
                ind.textContent = '';
                ind.parentElement.classList.remove('sorted');
            }
        });
    }
    
    // Track expanded event tasks
    let expandedEventTasks = {};
    
    // Toggle event tasks expansion
    function toggleEventTasks(eventId) {
        expandedEventTasks[eventId] = !expandedEventTasks[eventId];
        populateEventsTable();
    }
    
    // Open add task for specific event (from table)
    function openAddTaskForEvent(eventId, eventName) {
        document.getElementById('newTaskEventName').textContent = `📅 Event: ${eventName}`;
        
        // Reset form
        document.getElementById('newTaskForEventForm').reset();
        document.getElementById('newEventTaskStatus').value = 'To Do';
        document.getElementById('newEventTaskPriority').value = 'Medium';
        
        // Populate assignee dropdown
        const assigneeSelect = document.getElementById('newEventTaskAssignee');
        assigneeSelect.innerHTML = '<option value="">Unassigned</option>' + 
            users.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || u.username)}</option>`).join('');
        
        // Store event ID for submission
        window.addTaskForEventId = eventId;
        
        document.getElementById('newTaskForEventModal').style.display = 'block';
    }
    
    // Unlink task from event
    async function unlinkTaskFromEvent(taskId, eventId) {
        if (!confirm('Unlink this task from the event? The task will not be deleted.')) return;
        
        try {
            await fetch(`${API_URL}?action=unlinkTask`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId })
            });
            
            await fetchAllData();
            populateEventsTable();
        } catch (error) {
            console.error('Error unlinking task:', error);
            showToast('Error unlinking task', 'error');
        }
    }
    
    function getEventActionsHtml(event, canArchive) {
        if (window.isSpecialty) {
            return `<button class="btn btn-view btn-small" onclick="viewEvent(${event.id})" title="View">👁️</button>`;
        }
        
        let html = `
            <button class="btn btn-view btn-small" onclick="viewEvent(${event.id})" title="View">👁️</button>
            <button class="btn btn-edit btn-small" onclick="editEvent(${event.id})" title="Edit">✏️</button>
        `;
        
        if (canArchive) {
            html += `<button class="btn btn-delete btn-small" onclick="archiveEvent(${event.id}, '${escapeHtml(event.name || '')}')" title="Archive">📦</button>`;
        }
        
        return html;
    }
    
    function filterEventsTable() {
        populateEventsTable();
    }
    
    function sortEventsTable(column) {
        if (eventSortColumn === column) {
            eventSortDirection = eventSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            eventSortColumn = column;
            eventSortDirection = 'asc';
        }
        populateEventsTable();
    }
    
    // Edit/Create Event
    function editEvent(id = null) {
        currentEditId = id;
        const modal = document.getElementById('eventModal');
        const form = document.getElementById('eventForm');
        const title = document.getElementById('eventModalTitle');
        
        // Populate owner dropdown
        const ownerSelect = document.getElementById('eventOwner');
        ownerSelect.innerHTML = '<option value="">Select Owner</option>' + 
            users.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || u.username)}</option>`).join('');
        
        // Reset attendees, tasks, and notes for new events
        pendingEventAttendees = { users: [], federalContacts: [], commercialContacts: [] };
        pendingEventTasks = [];
        pendingEventNotes = [];
        
        // Show documents section only when editing (needs event ID for upload)
        document.getElementById('eventDocumentsSection').style.display = id ? 'block' : 'none';
        
        if (id) {
            title.textContent = 'Edit Event';
            const event = events.find(e => e.id == id);
            if (event) {
                document.getElementById('eventId').value = event.id;
                document.getElementById('eventName').value = event.name || '';
                document.getElementById('eventType').value = event.event_type || 'Meeting';
                document.getElementById('eventStatus').value = event.status || 'Planning';
                document.getElementById('eventStartDatetime').value = event.start_datetime ? event.start_datetime.replace(' ', 'T').substring(0, 16) : '';
                document.getElementById('eventEndDatetime').value = event.end_datetime ? event.end_datetime.replace(' ', 'T').substring(0, 16) : '';
                document.getElementById('eventLocation').value = event.location || '';
                document.getElementById('eventVirtualLink').value = event.virtual_link || '';
                document.getElementById('eventPriority').value = event.priority || 'Medium';
                document.getElementById('eventOwner').value = event.owner_user_id || '';
                document.getElementById('eventDescription').value = event.description || '';
                
                // Load assigned people into pending state
                // Load all data in one API call
                loadEventDataForModal(id);
                
                // Load documents
                loadEventDocuments(id);
            }
        } else {
            title.textContent = 'Add Event';
            form.reset();
            document.getElementById('eventId').value = '';
            document.getElementById('eventOwner').value = currentUserId;
            document.getElementById('eventPriority').value = 'Medium';
            document.getElementById('eventStatus').value = 'Planning';
            
            // Clear attendees list display
            renderEventAttendeesInModal();
            
            // Clear tasks list display
            renderEventTasksInModal();
            
            // Clear notes list display
            renderEventNotesInModal();
        }
        
        // Show/hide notes section and save button based on edit mode
        document.getElementById('eventNotesSection').style.display = id ? 'block' : 'block';
        document.getElementById('eventSaveBtn').style.display = 'block';
        
        modal.style.display = 'block';
    }
    
    // View Event (read-only modal)
    async function viewEvent(eventId) {
        currentEventId = eventId;
        const event = events.find(e => e.id == eventId);
        if (!event) return;
        
        document.getElementById('viewEventModalTitle').textContent = event.name || 'Event Details';
        
        // Render action buttons based on permissions
        const canUpdate = userPermissions.event?.can_update || currentRole === 'admin';
        let actionsHtml = '';
        if (canUpdate && !window.isSpecialty) {
            actionsHtml += `<button type="button" class="btn" onclick="editEventFromView()">✏️ Edit Event</button>`;
        }
        actionsHtml += `<button type="button" class="btn btn-secondary" onclick="closeModal('viewEventModal')">Close</button>`;
        document.getElementById('viewEventActions').innerHTML = actionsHtml;
        
        // Load full event details
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success && data.event) {
                renderViewEventContent(data.event);
            } else {
                // Fallback to basic event data
                renderViewEventContent(event);
            }
        } catch (error) {
            console.error('Error loading event details:', error);
            renderViewEventContent(event);
        }
        
        document.getElementById('viewEventModal').style.display = 'block';
    }
    
    // Render view event content
    function renderViewEventContent(event) {
        const ownerName = event.ownerDisplayName || event.ownerUsername || '—';
        const priorityColors = { 'Low': '#28a745', 'Medium': '#ffc107', 'High': '#dc3545' };
        const statusColors = {
            'Planning': '#17a2b8',
            'Confirmed': '#28a745',
            'In Progress': '#ffc107',
            'Completed': '#6c757d',
            'Cancelled': '#dc3545'
        };
        
        // Attendees HTML
        let attendeesHtml = '';
        const allAttendees = [
            ...(event.assignedUsers || []).map(u => ({ name: u.display_name || u.username, type: 'user', icon: '👤', color: '#667eea' })),
            ...(event.assignedFederalContacts || []).map(c => ({ name: c.display_name, detail: c.agencyName, type: 'federal', icon: '🏛️', color: '#28a745' })),
            ...(event.assignedCommercialContacts || []).map(c => ({ name: c.display_name, detail: c.companyName, type: 'commercial', icon: '🏢', color: '#fd7e14' }))
        ];
        
        if (allAttendees.length === 0) {
            attendeesHtml = '<p style="color: #6c757d; margin: 5px 0;">No attendees</p>';
        } else {
            attendeesHtml = allAttendees.map(a => `
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; background: #f8f9fa; border-radius: 15px; margin: 3px; border-left: 3px solid ${a.color};">
                    ${a.icon} ${escapeHtml(a.name)}${a.detail ? ` <small style="color: #6c757d;">(${escapeHtml(a.detail)})</small>` : ''}
                </span>
            `).join('');
        }
        
        // Tasks HTML
        let tasksHtml = '';
        const eventTasks = event.relatedTasks || tasks.filter(t => {
            const matchesType = (t.relatedTo === 'Event' || t.relatedTo === 'event');
            const matchesId = parseInt(t.related_item_id) === parseInt(event.id);
            return matchesType && matchesId;
        });
        if (eventTasks.length === 0) {
            tasksHtml = '<p style="color: #6c757d; margin: 5px 0;">No tasks linked</p>';
        } else {
            const taskStatusColors = { 'To Do': '#17a2b8', 'In Progress': '#ffc107', 'Done': '#28a745' };
            tasksHtml = eventTasks.map(t => `
                <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin: 5px 0; cursor: pointer; transition: background 0.2s;" 
                     onclick="openTaskFromEvent(${t.id})"
                     onmouseover="this.style.background='#e9ecef'" 
                     onmouseout="this.style.background='#f8f9fa'">
                    <div style="font-weight: 500; color: #667eea;">${escapeHtml(t.title)}</div>
                    ${t.description ? `<div style="font-size: 0.85rem; color: #6c757d; margin-top: 4px; white-space: pre-wrap; max-height: 60px; overflow: hidden;">${escapeHtml(t.description)}</div>` : ''}
                    <div style="display: flex; gap: 10px; margin-top: 5px; font-size: 0.85rem;">
                        <span class="badge" style="background: ${taskStatusColors[t.status] || '#6c757d'};">${t.status}</span>
                        <span style="color: #6c757d;">Due: ${t.dueDate || 'Not set'}</span>
                        <span style="color: #6c757d;">→ ${escapeHtml(t.assignedToDisplayName || t.assignee || '—')}</span>
                    </div>
                </div>
            `).join('');
        }
        
        // Notes HTML
        let notesHtml = '';
        if (event.notes && event.notes.length > 0) {
            notesHtml = event.notes.map(n => `
                <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin: 5px 0; border-left: 3px solid #667eea;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-weight: 500; color: #667eea;">${escapeHtml(n.interaction_type || 'General')}</span>
                        <span style="font-size: 0.8rem; color: #6c757d;">${formatNoteDate(n.created_at)}</span>
                    </div>
                    <div style="white-space: pre-wrap;">${escapeHtml(n.note_text || n.note || '')}</div>
                    <div style="font-size: 0.8rem; color: #6c757d; margin-top: 5px;">— ${escapeHtml(n.created_by_name || n.display_name || 'Unknown')}</div>
                </div>
            `).join('');
        } else {
            notesHtml = '<p style="color: #6c757d; margin: 5px 0;">No notes</p>';
        }
        
        // Documents HTML
        let documentsHtml = '';
        if (event.documents && event.documents.length > 0) {
            documentsHtml = event.documents.map(doc => `
                <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; margin: 5px 0;">
                    <span>${getFileIcon(doc.file_name)}</span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(doc.file_name)}</div>
                        <div style="font-size: 0.75rem; color: #6c757d;">
                            ${escapeHtml(doc.uploadedByDisplayName || 'Unknown')} • ${formatDate(doc.created_at)} • ${formatFileSize(doc.file_size)}
                        </div>
                    </div>
                    <a href="${doc.file_path}" download="${escapeHtml(doc.file_name)}" class="btn btn-small" title="Download" style="padding: 4px 8px;">⬇️</a>
                </div>
            `).join('');
        } else {
            documentsHtml = '<p style="color: #6c757d; margin: 5px 0;">No documents</p>';
        }
        
        const html = `
            <div class="form-grid" style="margin-bottom: 20px;">
                <div class="info-row" style="grid-column: span 2;">
                    <label style="font-weight: 600; color: #6c757d;">Event Type</label>
                    <span style="font-size: 1.1rem;">${getEventTypeIcon(event.event_type)} ${event.event_type || '—'}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Status</label>
                    <span class="badge" style="background: ${statusColors[event.status] || '#6c757d'};">${event.status || '—'}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Priority</label>
                    <span class="badge" style="background: ${priorityColors[event.priority] || '#ffc107'};">${event.priority || 'Medium'}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Start Date/Time</label>
                    <span>${formatEventDateTime(event.start_datetime)}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">End Date/Time</label>
                    <span>${event.end_datetime ? formatEventDateTime(event.end_datetime) : '—'}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Location</label>
                    <span>${escapeHtml(event.location || '—')}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Virtual Link</label>
                    <span>${event.virtual_link ? `<a href="${escapeHtml(event.virtual_link)}" target="_blank">${escapeHtml(event.virtual_link)}</a>` : '—'}</span>
                </div>
                <div class="info-row">
                    <label style="font-weight: 600; color: #6c757d;">Owner</label>
                    <span>${escapeHtml(ownerName)}</span>
                </div>
                <div class="info-row" style="grid-column: span 2;">
                    <label style="font-weight: 600; color: #6c757d;">Description</label>
                    <div style="white-space: pre-wrap; background: #f8f9fa; padding: 10px; border-radius: 6px; margin-top: 5px;">${escapeHtml(event.description || '—')}</div>
                </div>
            </div>
            
            <div style="border-top: 2px solid #e9ecef; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">🎟️ Attendees</h4>
                <div style="display: flex; flex-wrap: wrap;">${attendeesHtml}</div>
            </div>
            
            <div style="border-top: 2px solid #e9ecef; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">📋 Tasks</h4>
                ${tasksHtml}
            </div>
            
            <div style="border-top: 2px solid #e9ecef; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">📝 Notes</h4>
                <div style="max-height: 250px; overflow-y: auto;">${notesHtml}</div>
            </div>
            
            <div style="border-top: 2px solid #e9ecef; padding-top: 15px; margin-top: 15px;">
                <h4 style="margin: 0 0 10px 0; color: #667eea;">📎 Documents</h4>
                <div style="max-height: 200px; overflow-y: auto;">${documentsHtml}</div>
            </div>
        `;
        
        document.getElementById('viewEventContent').innerHTML = html;
    }
    
    // Get event type icon
    function getEventTypeIcon(type) {
        const icons = {
            'Meeting': '🤝',
            'Conference': '🏛️',
            'Training': '📚',
            'Webinar': '💻',
            'Site Visit': '🏢',
            'Review': '📋',
            'Workshop': '🔧',
            'Other': '📅'
        };
        return icons[type] || '📅';
    }
    
    // Edit event from view modal
    function editEventFromView() {
        closeModal('viewEventModal');
        editEvent(currentEventId);
    }
    
    // Open task from event context (closes event modals first)
    function openTaskFromEvent(taskId) {
        // Close any open event modals
        closeModal('viewEventModal');
        closeModal('eventModal');
        
        // Open the task edit modal
        editItem('task', taskId);
    }
    
    // Format note date
    function formatNoteDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }
    
    // Load notes for event modal
    let pendingEventNotes = [];
    
    async function loadEventNotesForModal(eventId) {
        const container = document.getElementById('eventModalNotesList');
        if (!container) return;
        
        try {
            const response = await fetch(`${API_URL}?action=getEventNotes&event_id=${eventId}`);
            const data = await response.json();
            
            if (data.success && data.notes && data.notes.length > 0) {
                // Mark loaded notes as existing (not new)
                pendingEventNotes = data.notes.map(n => ({
                    ...n,
                    isExisting: true,
                    isNew: false
                }));
                renderEventNotesInModal();
            } else {
                pendingEventNotes = [];
                renderEventNotesInModal();
            }
        } catch (error) {
            console.error('Error loading event notes:', error);
            container.innerHTML = '<p style="color: #dc3545; margin: 5px 0;">Error loading notes</p>';
        }
    }
    
    // Render notes in modal (handles both new and existing)
    function renderEventNotesInModal() {
        const container = document.getElementById('eventModalNotesList');
        if (!container) return;
        
        if (pendingEventNotes.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No notes yet. Click "Add Note" to add one.</p>';
            return;
        }
        
        // Get current user info for new notes display
        const currentUserName = users.find(u => u.id == currentUserId)?.display_name || 'You';
        
        container.innerHTML = pendingEventNotes.map((n, idx) => {
            const isNew = n.isNew;
            const noteText = n.note_text || n.note || '';
            const authorName = isNew ? currentUserName : (n.display_name || n.created_by_name || 'Unknown');
            const dateDisplay = isNew ? 'Just now' : formatNoteDate(n.created_at);
            
            return `
            <div style="background: ${isNew ? '#f0f4ff' : '#f8f9fa'}; padding: 12px; border-radius: 6px; margin: 5px 0; border-left: 3px solid ${isNew ? '#28a745' : '#667eea'};">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 5px;">
                            <span class="badge" style="background: #667eea;">${escapeHtml(n.interaction_type || 'General')}</span>
                            ${isNew ? '<span class="badge" style="background: #28a745;">New</span>' : ''}
                            <span style="font-size: 0.8rem; color: #6c757d;">${dateDisplay}</span>
                        </div>
                        <div style="white-space: pre-wrap;">${escapeHtml(noteText)}</div>
                        <div style="font-size: 0.8rem; color: #6c757d; margin-top: 5px;">— ${escapeHtml(authorName)}</div>
                    </div>
                    ${!window.isSpecialty ? `<button onclick="deleteEventNoteInModal(${isNew ? idx : n.id}, ${isNew})" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 1rem;" title="Delete">🗑️</button>` : ''}
                </div>
            </div>
        `}).join('');
    }
    
    // Open add note dialog from modal (works for both new and existing events)
    function openAddEventNoteInModal() {
        document.getElementById('eventNoteInModalForm').reset();
        document.getElementById('addEventNoteInModalDialog').style.display = 'block';
    }
    
    // Save note from modal form
    document.getElementById('eventNoteInModalForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const eventId = document.getElementById('eventId').value;
        const interactionType = document.getElementById('eventNoteInModalType').value;
        const noteText = document.getElementById('eventNoteInModalText').value;
        
        if (!noteText.trim()) {
            showToast('Please enter a note.', 'warning');
            return;
        }
        
        if (eventId) {
            // Existing event - save directly to API
            const noteData = {
                event_id: eventId,
                interaction_type: interactionType,
                note_text: noteText
            };
            
            try {
                const response = await fetch(`${API_URL}?action=saveEventNote`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(noteData)
                });
                
                const result = await response.json();
                if (result.success) {
                    closeModal('addEventNoteInModalDialog');
                    loadEventNotesForModal(eventId);
                } else {
                    showToast('Error saving note: ' + (result.error || 'Unknown error', 'error'));
                }
            } catch (error) {
                console.error('Error saving note:', error);
                showToast('Error saving note', 'error');
            }
        } else {
            // New event - add to pending list
            pendingEventNotes.push({
                interaction_type: interactionType,
                note_text: noteText,
                isNew: true,
                created_at: new Date().toISOString()
            });
            
            renderEventNotesInModal();
            closeModal('addEventNoteInModalDialog');
        }
    });
    
    // Delete note from modal (handles both pending and existing)
    async function deleteEventNoteInModal(idOrIndex, isNew = false) {
        if (!confirm('Delete this note?')) return;
        
        if (isNew) {
            // Remove from pending array by index
            pendingEventNotes.splice(idOrIndex, 1);
            renderEventNotesInModal();
        } else {
            // Delete from API
            try {
                const response = await fetch(`${API_URL}?action=deleteEventNote&id=${idOrIndex}`, { method: 'POST' });
                const result = await response.json();
                
                if (result.success) {
                    const eventId = document.getElementById('eventId').value;
                    loadEventNotesForModal(eventId);
                } else {
                    showToast('Error deleting note', 'error');
                }
            } catch (error) {
                console.error('Error deleting note:', error);
                showToast('Error deleting note', 'error');
            }
        }
    }
    
    // Save pending notes for an event (called after event is created/saved)
    async function saveEventNotes(eventId) {
        try {
            // Only save new notes (not existing ones that are already in DB)
            const newNotes = pendingEventNotes.filter(n => n.isNew);
            
            for (const note of newNotes) {
                await fetch(`${API_URL}?action=saveEventNote`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: eventId,
                        interaction_type: note.interaction_type,
                        note_text: note.note_text
                    })
                });
            }
        } catch (error) {
            console.error('Error saving event notes:', error);
        }
    }
    
    // Pending data for event modal (before save)
    let pendingEventAttendees = { users: [], federalContacts: [], commercialContacts: [] };
    let pendingEventTasks = [];
    let currentAttendeeFilter = 'all';
    
    // Load attendees for existing event
    // Load all event data for modal (consolidated single API call)
    async function loadEventDataForModal(eventId) {
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success && data.event) {
                // Load attendees
                pendingEventAttendees = {
                    users: (data.event.assignedUsers || []).map(u => ({
                        id: u.user_id,
                        name: u.display_name || u.username,
                        type: 'user'
                    })),
                    federalContacts: (data.event.assignedFederalContacts || []).map(c => ({
                        id: c.contact_id,
                        name: c.display_name,
                        agency: c.agencyName,
                        type: 'federal'
                    })),
                    commercialContacts: (data.event.assignedCommercialContacts || []).map(c => ({
                        id: c.contact_id,
                        name: c.display_name,
                        company: c.companyName,
                        type: 'commercial'
                    }))
                };
                renderEventAttendeesInModal();
                
                // Load tasks
                pendingEventTasks = (data.event.relatedTasks || []).map(t => ({
                    id: t.id,
                    title: t.title,
                    description: t.description || '',
                    status: t.status,
                    priority: t.priority,
                    dueDate: t.dueDate,
                    assignee: t.assignedToDisplayName,
                    isExisting: true
                }));
                renderEventTasksInModal();
                
                // Load notes
                pendingEventNotes = (data.event.notes || []).map(n => ({
                    ...n,
                    isExisting: true,
                    isNew: false
                }));
                renderEventNotesInModal();
            }
        } catch (error) {
            console.error('Error loading event data:', error);
        }
    }
    
    // Load attendees for existing event (kept for backwards compatibility)
    async function loadEventAttendeesForModal(eventId) {
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success && data.event) {
                pendingEventAttendees = {
                    users: (data.event.assignedUsers || []).map(u => ({
                        id: u.user_id,
                        name: u.display_name || u.username,
                        type: 'user'
                    })),
                    federalContacts: (data.event.assignedFederalContacts || []).map(c => ({
                        id: c.contact_id,
                        name: c.display_name,
                        agency: c.agencyName,
                        type: 'federal'
                    })),
                    commercialContacts: (data.event.assignedCommercialContacts || []).map(c => ({
                        id: c.contact_id,
                        name: c.display_name,
                        company: c.companyName,
                        type: 'commercial'
                    }))
                };
                renderEventAttendeesInModal();
            }
        } catch (error) {
            console.error('Error loading event attendees:', error);
        }
    }
    
    // Load tasks for existing event
    async function loadEventTasksForModal(eventId) {
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success && data.event && data.event.relatedTasks) {
                pendingEventTasks = data.event.relatedTasks.map(t => ({
                    id: t.id,
                    title: t.title,
                    description: t.description || '',
                    status: t.status,
                    priority: t.priority,
                    dueDate: t.dueDate,
                    assignee: t.assignedToDisplayName,
                    isExisting: true
                }));
                renderEventTasksInModal();
            }
        } catch (error) {
            console.error('Error loading event tasks:', error);
        }
    }
    
    // Render attendees in modal
    function renderEventAttendeesInModal() {
        const container = document.getElementById('eventAttendeesList');
        if (!container) return;
        
        const allAttendees = [
            ...pendingEventAttendees.users.map(u => ({ ...u, type: 'user' })),
            ...pendingEventAttendees.federalContacts.map(c => ({ ...c, type: 'federal' })),
            ...pendingEventAttendees.commercialContacts.map(c => ({ ...c, type: 'commercial' }))
        ];
        
        if (allAttendees.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No attendees added yet. Click "Add Attendee" to add people.</p>';
            return;
        }
        
        container.innerHTML = allAttendees.map(a => {
            const typeConfig = {
                user: { icon: '👤', bg: '#f0f4ff', color: '#667eea', label: 'User' },
                federal: { icon: '🏛️', bg: '#e8f5e9', color: '#2e7d32', label: 'Federal' },
                commercial: { icon: '🏢', bg: '#fff3e0', color: '#e65100', label: 'Commercial' }
            };
            const config = typeConfig[a.type] || typeConfig.user;
            const detail = a.agency || a.company || '';
            
            return `
                <div class="linked-chip" style="background: ${config.bg}; display: flex; align-items: center; gap: 8px; padding: 8px 12px; margin: 5px 0; border-radius: 6px; border-left: 3px solid ${config.color};">
                    <span>${config.icon}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 500;">${escapeHtml(a.name)}</div>
                        ${detail ? `<div style="font-size: 0.8rem; color: #6c757d;">${escapeHtml(detail)}</div>` : ''}
                    </div>
                    <span class="badge" style="background: ${config.color}; font-size: 0.7rem;">${config.label}</span>
                    <button type="button" onclick="removeEventAttendee('${a.type}', ${a.id})" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 1.1rem;">✕</button>
                </div>
            `;
        }).join('');
    }
    
    // Render tasks in modal
    function renderEventTasksInModal() {
        const container = document.getElementById('eventTasksList');
        if (!container) return;
        
        if (pendingEventTasks.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; margin: 5px 0; font-size: 0.9rem;">No tasks linked yet. Create a new task or link existing ones.</p>';
            return;
        }
        
        const statusColors = { 'To Do': '#17a2b8', 'In Progress': '#ffc107', 'Done': '#28a745' };
        const priorityColors = { 'Low': '#28a745', 'Medium': '#ffc107', 'High': '#dc3545' };
        
        container.innerHTML = pendingEventTasks.map((t, idx) => `
            <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; margin: 5px 0; display: flex; align-items: start; gap: 10px;">
                <div style="flex: 1;">
                    ${t.id && t.isExisting ? 
                        `<div style="font-weight: 500; color: #667eea; cursor: pointer;" onclick="openTaskFromEvent(${t.id})">${escapeHtml(t.title)}</div>` :
                        `<div style="font-weight: 500;">${escapeHtml(t.title)} <small style="color: #6c757d;">(unsaved)</small></div>`
                    }
                    ${t.description ? `<div style="font-size: 0.85rem; color: #6c757d; margin-top: 4px; white-space: pre-wrap; max-height: 40px; overflow: hidden;">${escapeHtml(t.description)}</div>` : ''}
                    <div style="display: flex; gap: 10px; margin-top: 5px; font-size: 0.85rem;">
                        <span class="badge" style="background: ${statusColors[t.status] || '#6c757d'};">${t.status || 'To Do'}</span>
                        <span class="badge" style="background: ${priorityColors[t.priority] || '#ffc107'};">${t.priority || 'Medium'}</span>
                        <span style="color: #6c757d;">Due: ${t.dueDate || 'Not set'}</span>
                        ${t.assignee ? `<span style="color: #6c757d;">→ ${escapeHtml(t.assignee)}</span>` : ''}
                    </div>
                </div>
                <button type="button" onclick="removeEventTask(${idx})" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 1.1rem;">✕</button>
            </div>
        `).join('');
    }
    
    // Remove attendee from pending list
    function removeEventAttendee(type, id) {
        if (type === 'user') {
            pendingEventAttendees.users = pendingEventAttendees.users.filter(u => u.id != id);
        } else if (type === 'federal') {
            pendingEventAttendees.federalContacts = pendingEventAttendees.federalContacts.filter(c => c.id != id);
        } else if (type === 'commercial') {
            pendingEventAttendees.commercialContacts = pendingEventAttendees.commercialContacts.filter(c => c.id != id);
        }
        renderEventAttendeesInModal();
    }
    
    // Remove task from pending list
    function removeEventTask(idx) {
        pendingEventTasks.splice(idx, 1);
        renderEventTasksInModal();
    }
    
    // Open unified attendee picker
    function openUnifiedAttendeePicker() {
        currentAttendeeFilter = 'all';
        document.querySelectorAll('.attendee-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === 'all');
            btn.classList.toggle('btn-secondary', btn.dataset.filter !== 'all');
        });
        document.getElementById('attendeeSearchInput').value = '';
        renderAttendeePickerList();
        document.getElementById('unifiedAttendeeModal').style.display = 'block';
    }
    
    // Set attendee filter
    function setAttendeeFilter(filter) {
        currentAttendeeFilter = filter;
        document.querySelectorAll('.attendee-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === filter);
            btn.classList.toggle('btn-secondary', btn.dataset.filter !== filter);
        });
        renderAttendeePickerList();
    }
    
    // Filter attendee list based on search
    function filterAttendeeList() {
        renderAttendeePickerList();
    }
    
    // Render attendee picker list
    function renderAttendeePickerList() {
        const container = document.getElementById('attendeePickerList');
        const searchTerm = (document.getElementById('attendeeSearchInput')?.value || '').toLowerCase();
        
        let allPeople = [];
        
        // Add users
        if (currentAttendeeFilter === 'all' || currentAttendeeFilter === 'user') {
            users.forEach(u => {
                const name = u.display_name || u.username;
                if (!searchTerm || name.toLowerCase().includes(searchTerm)) {
                    const isSelected = pendingEventAttendees.users.some(x => x.id == u.id);
                    allPeople.push({
                        id: u.id,
                        name: name,
                        detail: 'CRM User',
                        type: 'user',
                        icon: '👤',
                        bg: '#f0f4ff',
                        color: '#667eea',
                        selected: isSelected
                    });
                }
            });
        }
        
        // Add federal contacts
        if (currentAttendeeFilter === 'all' || currentAttendeeFilter === 'federal') {
            contacts.forEach(c => {
                const name = `${c.firstName || ''} ${c.lastName || ''}`.trim();
                if (!searchTerm || name.toLowerCase().includes(searchTerm) || (c.agencyName || '').toLowerCase().includes(searchTerm)) {
                    const isSelected = pendingEventAttendees.federalContacts.some(x => x.id == c.id);
                    allPeople.push({
                        id: c.id,
                        name: name,
                        detail: c.agencyName || 'Federal Contact',
                        agency: c.agencyName,
                        type: 'federal',
                        icon: '🏛️',
                        bg: '#e8f5e9',
                        color: '#2e7d32',
                        selected: isSelected
                    });
                }
            });
        }
        
        // Add commercial contacts
        if (currentAttendeeFilter === 'all' || currentAttendeeFilter === 'commercial') {
            companyContacts.forEach(c => {
                const name = `${c.first_name || ''} ${c.last_name || ''}`.trim();
                if (!searchTerm || name.toLowerCase().includes(searchTerm) || (c.companyName || '').toLowerCase().includes(searchTerm)) {
                    const isSelected = pendingEventAttendees.commercialContacts.some(x => x.id == c.id);
                    allPeople.push({
                        id: c.id,
                        name: name,
                        detail: c.companyName || 'Commercial Contact',
                        company: c.companyName,
                        type: 'commercial',
                        icon: '🏢',
                        bg: '#fff3e0',
                        color: '#e65100',
                        selected: isSelected
                    });
                }
            });
        }
        
        if (allPeople.length === 0) {
            container.innerHTML = '<p style="padding: 20px; text-align: center; color: #6c757d;">No matching contacts found</p>';
            return;
        }
        
        container.innerHTML = allPeople.map(p => `
            <div class="attendee-option" style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; ${p.selected ? 'background: #f0f4ff;' : ''}" onclick="toggleAttendeeSelection('${p.type}', ${p.id}, this)">
                <input type="checkbox" ${p.selected ? 'checked' : ''} style="pointer-events: none;">
                <span style="font-size: 1.2rem;">${p.icon}</span>
                <div style="flex: 1;">
                    <div style="font-weight: 500;">${escapeHtml(p.name)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d;">${escapeHtml(p.detail)}</div>
                </div>
                <span class="badge" style="background: ${p.color}; font-size: 0.7rem;">${p.type === 'user' ? 'User' : p.type === 'federal' ? 'Federal' : 'Commercial'}</span>
            </div>
        `).join('');
        
        updateAttendeeSelectedCount();
    }
    
    // Toggle attendee selection
    function toggleAttendeeSelection(type, id, element) {
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        element.style.background = checkbox.checked ? '#f0f4ff' : '';
        
        if (type === 'user') {
            const user = users.find(u => u.id == id);
            if (checkbox.checked) {
                if (!pendingEventAttendees.users.some(u => u.id == id)) {
                    pendingEventAttendees.users.push({ id: id, name: user.display_name || user.username, type: 'user' });
                }
            } else {
                pendingEventAttendees.users = pendingEventAttendees.users.filter(u => u.id != id);
            }
        } else if (type === 'federal') {
            const contact = contacts.find(c => c.id == id);
            if (checkbox.checked) {
                if (!pendingEventAttendees.federalContacts.some(c => c.id == id)) {
                    pendingEventAttendees.federalContacts.push({ 
                        id: id, 
                        name: `${contact.firstName || ''} ${contact.lastName || ''}`.trim(),
                        agency: contact.agencyName,
                        type: 'federal' 
                    });
                }
            } else {
                pendingEventAttendees.federalContacts = pendingEventAttendees.federalContacts.filter(c => c.id != id);
            }
        } else if (type === 'commercial') {
            const contact = companyContacts.find(c => c.id == id);
            if (checkbox.checked) {
                if (!pendingEventAttendees.commercialContacts.some(c => c.id == id)) {
                    pendingEventAttendees.commercialContacts.push({ 
                        id: id, 
                        name: `${contact.first_name || ''} ${contact.last_name || ''}`.trim(),
                        company: contact.companyName,
                        type: 'commercial' 
                    });
                }
            } else {
                pendingEventAttendees.commercialContacts = pendingEventAttendees.commercialContacts.filter(c => c.id != id);
            }
        }
        
        updateAttendeeSelectedCount();
    }
    
    // Update selected count display
    function updateAttendeeSelectedCount() {
        const total = pendingEventAttendees.users.length + 
                      pendingEventAttendees.federalContacts.length + 
                      pendingEventAttendees.commercialContacts.length;
        document.getElementById('attendeeSelectedCount').textContent = `${total} selected`;
    }
    
    // Add selected attendees
    function addSelectedAttendees() {
        renderEventAttendeesInModal();
        closeModal('unifiedAttendeeModal');
    }
    
    // Open link task modal
    function openLinkTaskToEventModal() {
        document.getElementById('linkTaskSearchInput').value = '';
        renderLinkTaskList();
        document.getElementById('linkTaskToEventModal').style.display = 'block';
    }
    
    // Filter link task list
    function filterLinkTaskList() {
        renderLinkTaskList();
    }
    
    // Render link task list
    function renderLinkTaskList() {
        const container = document.getElementById('linkTaskList');
        const searchTerm = (document.getElementById('linkTaskSearchInput')?.value || '').toLowerCase();
        
        // Filter tasks that are not already linked and not already linked to this event
        const availableTasks = tasks.filter(t => {
            // Exclude tasks already in pending list
            if (pendingEventTasks.some(pt => pt.id == t.id)) return false;
            // Exclude tasks already linked to other events (optional - you might want to allow this)
            // if (t.relatedTo === 'event') return false;
            // Search filter
            if (searchTerm && !t.title.toLowerCase().includes(searchTerm)) return false;
            return true;
        });
        
        if (availableTasks.length === 0) {
            container.innerHTML = '<p style="padding: 20px; text-align: center; color: #6c757d;">No available tasks found</p>';
            return;
        }
        
        const statusColors = { 'To Do': '#17a2b8', 'In Progress': '#ffc107', 'Done': '#28a745' };
        
        container.innerHTML = availableTasks.map(t => `
            <div class="link-task-option" style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer;" onclick="toggleLinkTaskSelection(${t.id}, this)">
                <input type="checkbox" data-task-id="${t.id}" style="pointer-events: none;">
                <div style="flex: 1;">
                    <div style="font-weight: 500;">${escapeHtml(t.title)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d; display: flex; gap: 10px;">
                        <span class="badge" style="background: ${statusColors[t.status] || '#6c757d'};">${t.status}</span>
                        <span>Due: ${t.dueDate || 'Not set'}</span>
                        <span>Assignee: ${t.assignedToDisplayName || '—'}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // Toggle link task selection
    function toggleLinkTaskSelection(taskId, element) {
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        element.style.background = checkbox.checked ? '#f0f4ff' : '';
    }
    
    // Link selected tasks to event
    function linkSelectedTasksToEvent() {
        const checkboxes = document.querySelectorAll('#linkTaskList input[type="checkbox"]:checked');
        
        checkboxes.forEach(cb => {
            const taskId = parseInt(cb.dataset.taskId);
            const task = tasks.find(t => t.id == taskId);
            if (task && !pendingEventTasks.some(pt => pt.id == taskId)) {
                pendingEventTasks.push({
                    id: task.id,
                    title: task.title,
                    status: task.status,
                    priority: task.priority,
                    dueDate: task.dueDate,
                    assignee: task.assignedToDisplayName,
                    isExisting: true
                });
            }
        });
        
        renderEventTasksInModal();
        closeModal('linkTaskToEventModal');
    }
    
    // Open new task for event modal
    function openNewTaskForEventModal() {
        const eventName = document.getElementById('eventName').value || 'New Event';
        document.getElementById('newTaskEventName').textContent = `📅 Event: ${eventName}`;
        
        // Reset form
        document.getElementById('newTaskForEventForm').reset();
        document.getElementById('newEventTaskStatus').value = 'To Do';
        document.getElementById('newEventTaskPriority').value = 'Medium';
        
        // Populate assignee dropdown
        const assigneeSelect = document.getElementById('newEventTaskAssignee');
        assigneeSelect.innerHTML = '<option value="">Unassigned</option>' + 
            users.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || u.username)}</option>`).join('');
        
        document.getElementById('newTaskForEventModal').style.display = 'block';
    }
    
    // Handle new task for event form submission
    document.getElementById('newTaskForEventForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const title = document.getElementById('newEventTaskTitle').value.trim();
        if (!title) return;
        
        const assigneeId = document.getElementById('newEventTaskAssignee').value;
        const assignee = assigneeId ? users.find(u => u.id == assigneeId) : null;
        
        // Check if we're adding to an existing event (from table) or to pending list (from modal)
        if (window.addTaskForEventId) {
            // Direct add to existing event
            try {
                await fetch(`${API_URL}?action=saveTask`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: title,
                        status: document.getElementById('newEventTaskStatus').value,
                        priority: document.getElementById('newEventTaskPriority').value,
                        dueDate: document.getElementById('newEventTaskDueDate').value || null,
                        assigned_to_user_id: assigneeId || null,
                        description: document.getElementById('newEventTaskDescription').value,
                        relatedTo: 'Event',
                        related_item_id: window.addTaskForEventId
                    })
                });
                
                window.addTaskForEventId = null;
                closeModal('newTaskForEventModal');
                await fetchAllData();
                populateEventsTable();
            } catch (error) {
                console.error('Error creating task:', error);
                showToast('Error creating task', 'error');
            }
        } else {
            // Add to pending list (for event modal)
            pendingEventTasks.push({
                id: null,
                title: title,
                status: document.getElementById('newEventTaskStatus').value,
                priority: document.getElementById('newEventTaskPriority').value,
                dueDate: document.getElementById('newEventTaskDueDate').value || null,
                assignee: assignee ? (assignee.display_name || assignee.username) : null,
                assigneeId: assigneeId || null,
                description: document.getElementById('newEventTaskDescription').value,
                isNew: true
            });
            
            renderEventTasksInModal();
            closeModal('newTaskForEventModal');
        }
    });
    
    // Open people management modal (legacy)
    async function openEventPeopleModal() {
        const eventId = document.getElementById('eventId').value;
        if (!eventId) {
            showToast('Please save the event first before managing people.', 'warning');
            return;
        }
        
        const modal = document.getElementById('eventPeopleModal');
        
        // Populate users list
        const usersList = document.getElementById('eventUsersList');
        usersList.innerHTML = users.map(u => `
            <label style="display: flex; align-items: center; gap: 8px; padding: 5px; cursor: pointer;">
                <input type="checkbox" class="event-user-checkbox" value="${u.id}">
                <span>${escapeHtml(u.display_name || u.username)}</span>
            </label>
        `).join('');
        
        // Populate federal contacts list
        const fedList = document.getElementById('eventFedContactsList');
        fedList.innerHTML = contacts.map(c => `
            <label style="display: flex; align-items: center; gap: 8px; padding: 5px; cursor: pointer;" data-name="${c.firstName} ${c.lastName}">
                <input type="checkbox" class="event-fed-contact-checkbox" value="${c.id}">
                <span>${escapeHtml(c.firstName)} ${escapeHtml(c.lastName)}</span>
                <small style="color: #6c757d;">${escapeHtml(c.agencyName || '')}</small>
            </label>
        `).join('');
        
        // Populate commercial contacts list
        const commList = document.getElementById('eventCommContactsList');
        commList.innerHTML = companyContacts.map(c => `
            <label style="display: flex; align-items: center; gap: 8px; padding: 5px; cursor: pointer;" data-name="${c.first_name} ${c.last_name}">
                <input type="checkbox" class="event-comm-contact-checkbox" value="${c.id}">
                <span>${escapeHtml(c.first_name)} ${escapeHtml(c.last_name)}</span>
                <small style="color: #6c757d;">${escapeHtml(c.companyName || '')}</small>
            </label>
        `).join('');
        
        // Load current assignments
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success) {
                // Check users
                (data.event.assignedUsers || []).forEach(u => {
                    const checkbox = usersList.querySelector(`input[value="${u.user_id}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                
                // Check federal contacts
                (data.event.assignedFederalContacts || []).forEach(c => {
                    const checkbox = fedList.querySelector(`input[value="${c.contact_id}"]`);
                    if (checkbox) checkbox.checked = true;
                });
                
                // Check commercial contacts
                (data.event.assignedCommercialContacts || []).forEach(c => {
                    const checkbox = commList.querySelector(`input[value="${c.contact_id}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            }
        } catch (error) {
            console.error('Error loading event assignments:', error);
        }
        
        modal.style.display = 'block';
    }
    
    function filterEventFedContacts() {
        const search = document.getElementById('eventFedContactSearch').value.toLowerCase();
        document.querySelectorAll('#eventFedContactsList label').forEach(label => {
            const name = label.dataset.name?.toLowerCase() || '';
            label.style.display = name.includes(search) ? '' : 'none';
        });
    }
    
    function filterEventCommContacts() {
        const search = document.getElementById('eventCommContactSearch').value.toLowerCase();
        document.querySelectorAll('#eventCommContactsList label').forEach(label => {
            const name = label.dataset.name?.toLowerCase() || '';
            label.style.display = name.includes(search) ? '' : 'none';
        });
    }
    
    async function saveEventPeopleAssignments() {
        const eventId = document.getElementById('eventId').value;
        if (!eventId) return;
        
        const userIds = Array.from(document.querySelectorAll('.event-user-checkbox:checked')).map(cb => cb.value);
        const fedContactIds = Array.from(document.querySelectorAll('.event-fed-contact-checkbox:checked')).map(cb => cb.value);
        const commContactIds = Array.from(document.querySelectorAll('.event-comm-contact-checkbox:checked')).map(cb => cb.value);
        
        try {
            const response = await fetch(`${API_URL}?action=saveEventAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_id: eventId,
                    user_ids: userIds,
                    federal_contact_ids: fedContactIds,
                    commercial_contact_ids: commContactIds
                })
            });
            
            const result = await response.json();
            if (result.success) {
                closeModal('eventPeopleModal');
                loadEventAssignedPeople(eventId);
                await fetchAllData(); // Refresh to update people counts
            } else {
                showToast('Error saving assignments: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving assignments:', error);
            showToast('Error saving assignments', 'error');
        }
    }
    
    // Event form submission
    document.getElementById('eventForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const eventData = {
            id: document.getElementById('eventId').value || null,
            name: document.getElementById('eventName').value,
            event_type: document.getElementById('eventType').value,
            status: document.getElementById('eventStatus').value,
            start_datetime: document.getElementById('eventStartDatetime').value,
            end_datetime: document.getElementById('eventEndDatetime').value || null,
            location: document.getElementById('eventLocation').value,
            virtual_link: document.getElementById('eventVirtualLink').value,
            priority: document.getElementById('eventPriority').value,
            owner_user_id: document.getElementById('eventOwner').value || null,
            description: document.getElementById('eventDescription').value
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveEvent`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(eventData)
            });
            
            const result = await response.json();
            if (result.success) {
                const eventId = result.id || eventData.id;
                
                // Save attendees
                await saveEventAttendees(eventId);
                
                // Save/create tasks
                await saveEventTasks(eventId);
                
                // Save notes
                await saveEventNotes(eventId);
                
                closeModal('eventModal');
                await fetchAllData();
                populateEventsTable();
            } else {
                showToast('Error saving event: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving event:', error);
            showToast('Error saving event', 'error');
        }
    });
    
    // Save attendees for an event
    async function saveEventAttendees(eventId) {
        try {
            await fetch(`${API_URL}?action=saveEventAssignments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    event_id: eventId,
                    user_ids: pendingEventAttendees.users.map(u => u.id),
                    federal_contact_ids: pendingEventAttendees.federalContacts.map(c => c.id),
                    commercial_contact_ids: pendingEventAttendees.commercialContacts.map(c => c.id)
                })
            });
        } catch (error) {
            console.error('Error saving event attendees:', error);
        }
    }
    
    // Save/create tasks for an event
    async function saveEventTasks(eventId) {
        try {
            for (const task of pendingEventTasks) {
                if (task.isNew) {
                    // Create new task linked to event
                    await fetch(`${API_URL}?action=saveTask`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            title: task.title,
                            status: task.status,
                            priority: task.priority,
                            dueDate: task.dueDate,
                            assigned_to_user_id: task.assigneeId,
                            description: task.description,
                            relatedTo: 'Event',
                            related_item_id: eventId
                        })
                    });
                } else if (task.isExisting && task.id) {
                    // Update existing task to link to this event
                    await fetch(`${API_URL}?action=linkTaskToEvent`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            task_id: task.id,
                            event_id: eventId
                        })
                    });
                }
            }
        } catch (error) {
            console.error('Error saving event tasks:', error);
        }
    }
    
    // Archive event
    async function archiveEvent(id, name) {
        if (!confirm(`Are you sure you want to archive "${name}"?\n\nThis will move the event and all related tasks to the archive. You can restore it later from the Admin Panel.`)) return;
        
        try {
            const response = await fetch(`${API_URL}?action=archiveEvent`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                showToast('Event archived successfully.', 'success');
                await fetchAllData();
                populateEventsTable();
            } else {
                showToast('Error archiving event: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error archiving event:', error);
            showToast('Error archiving event', 'error');
        }
    }
    
    // Event documents
    async function loadEventDocuments(eventId) {
        const container = document.getElementById('eventDocumentsList');
        if (!container) return;
        
        try {
            const response = await fetch(`${API_URL}?action=getEventDocuments&event_id=${eventId}`);
            const data = await response.json();
            
            if (!data.success || !data.documents || data.documents.length === 0) {
                container.innerHTML = '<p style="color: #6c757d; margin: 0;">No documents uploaded</p>';
                return;
            }
            
            container.innerHTML = data.documents.map(doc => `
                <div class="document-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; margin-bottom: 5px;">
                    <span class="doc-icon">${getFileIcon(doc.file_name)}</span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(doc.file_name)}</div>
                        <div style="font-size: 0.75rem; color: #6c757d;">
                            ${doc.uploadedByDisplayName || 'Unknown'} • ${formatDate(doc.created_at)} • ${formatFileSize(doc.file_size)}
                        </div>
                    </div>
                    <a href="${doc.file_path}" download="${escapeHtml(doc.file_name)}" class="btn btn-small" title="Download">⬇</a>
                    ${!window.isSpecialty ? `<button class="btn btn-small btn-delete" onclick="deleteEventDocument(${doc.id})" title="Delete">✕</button>` : ''}
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading documents:', error);
            container.innerHTML = '<p style="color: #dc3545;">Error loading documents</p>';
        }
    }
    
    async function uploadEventDocument() {
        const fileInput = document.getElementById('eventDocumentUpload');
        const eventId = document.getElementById('eventId').value;
        
        if (!fileInput.files[0] || !eventId) return;
        
        const formData = new FormData();
        formData.append('document', fileInput.files[0]);
        formData.append('event_id', eventId);
        
        try {
            const response = await fetch(`${API_URL}?action=uploadEventDocument`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                fileInput.value = '';
                loadEventDocuments(eventId);
            } else {
                showToast('Error uploading document: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            showToast('Error uploading document', 'error');
        }
    }
    
    async function deleteEventDocument(docId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        const eventId = document.getElementById('eventId').value;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteEventDocument`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: docId })
            });
            
            const result = await response.json();
            if (result.success) {
                loadEventDocuments(eventId);
            } else {
                showToast('Error deleting document: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showToast('Error deleting document', 'error');
        }
    }
    
    // ==================== EVENT PANEL ====================
    
    async function openEventPanel(eventId) {
        currentEventId = eventId;
        const event = events.find(e => e.id == eventId);
        if (!event) return;
        
        // Set header info
        document.getElementById('eventPanelTitle').textContent = event.name || 'Untitled Event';
        document.getElementById('eventPanelSubtitle').textContent = `${event.event_type || ''} - ${event.status || ''}`;
        
        // Load full event details
        await loadEventDetails(eventId);
        
        // Populate note filter dropdowns
        populateEventNoteFilters();
        
        // Load notes
        loadEventNotes();
        
        // Switch to info tab
        switchEventTab('info');
        
        // Show panel
        document.getElementById('eventPanelOverlay').classList.add('open');
        document.getElementById('eventPanel').classList.add('open');
    }
    
    function closeEventPanel() {
        document.getElementById('eventPanelOverlay').classList.remove('open');
        document.getElementById('eventPanel').classList.remove('open');
        currentEventId = null;
        currentEventData = null;
        currentEventNotes = [];
    }
    
    function switchEventTab(tab) {
        document.querySelectorAll('#eventPanel .contact-panel-tab').forEach(t => {
            t.classList.remove('active');
            if ((tab === 'info' && t.textContent === 'Info') || (tab === 'notes' && t.textContent === 'Notes')) {
                t.classList.add('active');
            }
        });
        
        if (tab === 'info') {
            document.getElementById('eventInfoSection').style.display = 'block';
            document.getElementById('eventNotesSection').style.display = 'none';
        } else {
            document.getElementById('eventInfoSection').style.display = 'none';
            document.getElementById('eventNotesSection').style.display = 'block';
        }
    }
    
    async function loadEventDetails(eventId) {
        try {
            const response = await fetch(`${API_URL}?action=getEventDetails&id=${eventId}`);
            const data = await response.json();
            
            if (data.success) {
                currentEventData = data.event;
                renderEventInfo();
            }
        } catch (error) {
            console.error('Error loading event details:', error);
        }
    }
    
    function renderEventInfo() {
        const event = currentEventData;
        if (!event) return;
        
        const ownerName = event.ownerDisplayName || event.ownerUsername || '—';
        
        // Priority badge
        const priorityColors = { 'Low': '#28a745', 'Medium': '#ffc107', 'High': '#dc3545' };
        const priorityBadge = event.priority ? 
            `<span class="badge" style="background: ${priorityColors[event.priority] || '#6c757d'};">${event.priority}</span>` : '—';
        
        // Build assigned users HTML
        let assignedUsersHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (event.assignedUsers && event.assignedUsers.length > 0) {
            assignedUsersHtml = event.assignedUsers.map(u => 
                `<span class="owner-badge" title="CRM User">👤 ${escapeHtml(u.display_name || u.username)}</span>`
            ).join(' ');
        }
        
        // Build assigned federal contacts HTML
        let assignedFedHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (event.assignedFederalContacts && event.assignedFederalContacts.length > 0) {
            assignedFedHtml = event.assignedFederalContacts.map(c => 
                `<span class="owner-badge" style="background: #e8f5e9; color: #2e7d32;" title="Federal Contact - ${escapeHtml(c.agencyName || '')}">🏛️ ${escapeHtml(c.display_name)}</span>`
            ).join(' ');
        }
        
        // Build assigned commercial contacts HTML
        let assignedCommHtml = '<span style="color: #6c757d;">None assigned</span>';
        if (event.assignedCommercialContacts && event.assignedCommercialContacts.length > 0) {
            assignedCommHtml = event.assignedCommercialContacts.map(c => 
                `<span class="owner-badge" style="background: #fff3e0; color: #e65100;" title="Commercial Contact - ${escapeHtml(c.companyName || '')}">🏢 ${escapeHtml(c.display_name)}</span>`
            ).join(' ');
        }
        
        // Related tasks HTML
        let tasksHtml = '<p style="color: #6c757d; margin: 5px 0;">No related tasks</p>';
        if (event.relatedTasks && event.relatedTasks.length > 0) {
            tasksHtml = event.relatedTasks.map(t => {
                const statusColors = { 'To Do': '#17a2b8', 'In Progress': '#ffc107', 'Done': '#28a745' };
                return `<div style="padding: 8px; background: #f8f9fa; border-radius: 6px; margin-bottom: 5px; cursor: pointer; transition: background 0.2s;" 
                             onclick="openTaskFromEvent(${t.id})"
                             onmouseover="this.style.background='#e9ecef'" 
                             onmouseout="this.style.background='#f8f9fa'">
                    <div style="font-weight: 500; color: #667eea;">${escapeHtml(t.title)}</div>
                    <div style="font-size: 0.85rem; color: #6c757d; display: flex; gap: 10px; margin-top: 3px;">
                        <span class="badge" style="background: ${statusColors[t.status] || '#6c757d'};">${t.status}</span>
                        <span>Due: ${t.dueDate ? formatEventDate(t.dueDate) : 'No date'}</span>
                        <span>Assigned: ${t.assignedToDisplayName || '—'}</span>
                    </div>
                </div>`;
            }).join('');
        }
        
        // Documents section
        let docsHtml = '<p style="color: #6c757d; margin: 5px 0;">No documents</p>';
        // We'll load documents separately
        
        const infoHtml = `
            <div class="contact-info-grid">
                <div class="info-item">
                    <label>Event Type</label>
                    <span>${event.event_type || '—'}</span>
                </div>
                <div class="info-item">
                    <label>Status</label>
                    <span>${event.status || '—'}</span>
                </div>
                <div class="info-item">
                    <label>Priority</label>
                    <span>${priorityBadge}</span>
                </div>
                <div class="info-item">
                    <label>Owner</label>
                    <span>${escapeHtml(ownerName)}</span>
                </div>
                <div class="info-item">
                    <label>Start Date/Time</label>
                    <span>${formatEventDateTime(event.start_datetime)}</span>
                </div>
                <div class="info-item">
                    <label>End Date/Time</label>
                    <span>${event.end_datetime ? formatEventDateTime(event.end_datetime) : '—'}</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <label>Location</label>
                    <span>${escapeHtml(event.location || '—')}</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <label>Virtual Link</label>
                    <span>${event.virtual_link ? `<a href="${escapeHtml(event.virtual_link)}" target="_blank">${escapeHtml(event.virtual_link)}</a>` : '—'}</span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <label>Description</label>
                    <span style="white-space: pre-wrap;">${escapeHtml(event.description || '—')}</span>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #667eea;">👤 Assigned Users</h4>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">${assignedUsersHtml}</div>
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                <h4 style="margin: 0 0 10px 0; color: #28a745;">🏛️ Federal Contacts</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">${assignedFedHtml}</div>
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                <h4 style="margin: 0 0 10px 0; color: #fd7e14;">🏢 Commercial Contacts</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 5px;">${assignedCommHtml}</div>
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #667eea;">📋 Related Tasks</h4>
                </div>
                ${tasksHtml}
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h4 style="margin: 0; color: #667eea;">📎 Documents</h4>
                    ${!window.isSpecialty ? `
                    <label class="btn btn-small" style="margin: 0; cursor: pointer;">
                        + Upload
                        <input type="file" id="eventPanelDocUpload" style="display: none;" 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip"
                               onchange="uploadEventPanelDocument()">
                    </label>` : ''}
                </div>
                <div id="eventPanelDocsList">Loading...</div>
            </div>
            
            <div style="margin-top: 20px;">
                ${!window.isSpecialty ? `<button class="btn" onclick="closeEventPanel(); editEvent(${event.id})">✏️ Edit Event</button>` : ''}
            </div>
        `;
        
        document.getElementById('eventInfoSection').innerHTML = infoHtml;
        
        // Load documents for panel
        loadEventPanelDocuments(event.id);
    }
    
    async function loadEventPanelDocuments(eventId) {
        const container = document.getElementById('eventPanelDocsList');
        if (!container) return;
        
        try {
            const response = await fetch(`${API_URL}?action=getEventDocuments&event_id=${eventId}`);
            const data = await response.json();
            
            if (!data.success || !data.documents || data.documents.length === 0) {
                container.innerHTML = '<p style="color: #6c757d; margin: 0;">No documents uploaded</p>';
                return;
            }
            
            container.innerHTML = data.documents.map(doc => `
                <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #f8f9fa; border-radius: 6px; margin-bottom: 5px;">
                    <span>${getFileIcon(doc.file_name)}</span>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(doc.file_name)}</div>
                        <div style="font-size: 0.75rem; color: #6c757d;">${formatFileSize(doc.file_size)}</div>
                    </div>
                    <a href="${doc.file_path}" download class="btn btn-small" title="Download">⬇</a>
                    ${!window.isSpecialty ? `<button class="btn btn-small btn-delete" onclick="deleteEventPanelDocument(${doc.id}, ${eventId})" title="Delete">✕</button>` : ''}
                </div>
            `).join('');
        } catch (error) {
            console.error('Error loading documents:', error);
            container.innerHTML = '<p style="color: #dc3545;">Error loading documents</p>';
        }
    }
    
    async function uploadEventPanelDocument() {
        const fileInput = document.getElementById('eventPanelDocUpload');
        const eventId = currentEventId;
        
        if (!fileInput.files[0] || !eventId) return;
        
        const formData = new FormData();
        formData.append('document', fileInput.files[0]);
        formData.append('event_id', eventId);
        
        try {
            const response = await fetch(`${API_URL}?action=uploadEventDocument`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                fileInput.value = '';
                loadEventPanelDocuments(eventId);
            } else {
                showToast('Error uploading document: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            showToast('Error uploading document', 'error');
        }
    }
    
    async function deleteEventPanelDocument(docId, eventId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteEventDocument`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: docId })
            });
            
            const result = await response.json();
            if (result.success) {
                loadEventPanelDocuments(eventId);
            } else {
                showToast('Error deleting document: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showToast('Error deleting document', 'error');
        }
    }
    
    // Event Notes
    function populateEventNoteFilters() {
        const userSelect = document.getElementById('eventNoteFilterUser');
        if (userSelect) {
            userSelect.innerHTML = '<option value="">All Users</option>' + 
                users.map(u => `<option value="${u.id}">${escapeHtml(u.display_name || u.username)}</option>`).join('');
        }
    }
    
    async function loadEventNotes() {
        if (!currentEventId) return;
        
        const filterDateFrom = document.getElementById('eventNoteFilterDateFrom')?.value || '';
        const filterDateTo = document.getElementById('eventNoteFilterDateTo')?.value || '';
        const filterUserId = document.getElementById('eventNoteFilterUser')?.value || '';
        const filterType = document.getElementById('eventNoteFilterType')?.value || '';
        
        let url = `${API_URL}?action=getEventNotes&event_id=${currentEventId}`;
        if (filterDateFrom) url += `&filter_date_from=${filterDateFrom}`;
        if (filterDateTo) url += `&filter_date_to=${filterDateTo}`;
        if (filterUserId) url += `&filter_user_id=${filterUserId}`;
        if (filterType) url += `&filter_interaction_type=${encodeURIComponent(filterType)}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                currentEventNotes = data.notes || [];
                renderEventNotes();
            }
        } catch (error) {
            console.error('Error loading notes:', error);
        }
    }
    
    function filterEventNotes() {
        loadEventNotes();
    }
    
    function renderEventNotes() {
        const container = document.getElementById('eventNotesList');
        if (!container) return;
        
        if (currentEventNotes.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #6c757d; padding: 20px;">No notes found</p>';
            return;
        }
        
        container.innerHTML = currentEventNotes.map(note => {
            const isOwner = note.user_id == currentUserId;
            return `
                <div class="note-item">
                    <div class="note-header">
                        <strong>${escapeHtml(note.display_name || note.username)}</strong>
                        <span class="note-date">${formatDate(note.created_at)}</span>
                    </div>
                    ${note.interaction_type ? `<div class="note-type">${escapeHtml(note.interaction_type)}</div>` : ''}
                    ${note.note_date ? `<div style="font-size: 0.85rem; color: #667eea; margin-bottom: 5px;">${new Date(note.note_date + 'T00:00:00').toLocaleDateString()}</div>` : ''}
                    <div class="note-content">${escapeHtml(note.note_text)}</div>
                    ${isOwner ? `
                        <div class="note-actions">
                            <button class="btn btn-small" onclick="editEventNote(${note.id})">Edit</button>
                            <button class="btn btn-small btn-delete" onclick="deleteEventNote(${note.id})">Delete</button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }
    
    function openAddEventNoteModal() {
        document.getElementById('eventNoteModalTitle').textContent = 'Add Note';
        document.getElementById('eventNoteId').value = '';
        document.getElementById('eventNoteEventId').value = currentEventId;
        document.getElementById('eventNoteInteractionType').value = '';
        document.getElementById('eventNoteText').value = '';
        document.getElementById('eventNoteDate').value = new Date().toISOString().split('T')[0];

        document.getElementById('eventNoteModal').style.display = 'block';
    }
    
    function closeEventNoteModal() {
        document.getElementById('eventNoteModal').style.display = 'none';
    }
    
    function editEventNote(noteId) {
        const note = currentEventNotes.find(n => n.id == noteId);
        if (!note) return;
        
        document.getElementById('eventNoteModalTitle').textContent = 'Edit Note';
        document.getElementById('eventNoteId').value = note.id;
        document.getElementById('eventNoteEventId').value = note.event_id;
        document.getElementById('eventNoteInteractionType').value = note.interaction_type || '';
        document.getElementById('eventNoteText').value = note.note_text || '';
        document.getElementById('eventNoteDate').value = note.note_date || '';

        document.getElementById('eventNoteModal').style.display = 'block';
    }
    
    async function saveEventNote(e) {
        e.preventDefault();
        
        const noteData = {
            id: document.getElementById('eventNoteId').value || null,
            event_id: document.getElementById('eventNoteEventId').value,
            note_date: document.getElementById('eventNoteDate').value || null,
            interaction_type: document.getElementById('eventNoteInteractionType').value,
            note_text: document.getElementById('eventNoteText').value
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveEventNote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            
            const result = await response.json();
            if (result.success) {
                closeEventNoteModal();
                loadEventNotes();
            } else {
                showToast('Error saving note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving note:', error);
            showToast('Error saving note', 'error');
        }
    }
    
    async function deleteEventNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteEventNote&id=${noteId}`);
            const result = await response.json();
            
            if (result.success) {
                loadEventNotes();
            } else {
                showToast('Error deleting note: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showToast('Error deleting note', 'error');
        }
    }

    // ==================== DEPARTMENT CALENDAR ====================
    
    // State variables
    let deptCalendarDate = new Date();
    let deptCalendarView = 'month';
    let deptEvents = [];
    let currentDeptEvent = null;
    let pendingRecurringAction = null;
    
    // Color palette for events
    const deptColorPalette = [
        { name: 'Purple', color: '#667eea' },
        { name: 'Violet', color: '#764ba2' },
        { name: 'Blue', color: '#007bff' },
        { name: 'Teal', color: '#17a2b8' },
        { name: 'Green', color: '#28a745' },
        { name: 'Lime', color: '#7cb342' },
        { name: 'Yellow', color: '#ffc107' },
        { name: 'Orange', color: '#fd7e14' },
        { name: 'Red', color: '#dc3545' },
        { name: 'Pink', color: '#e83e8c' },
        { name: 'Gray', color: '#6c757d' },
        { name: 'Brown', color: '#795548' },
    ];
    
    // Initialize department calendar
    function initDeptCalendar() {
        renderDeptColorPalette();
        loadDeptEvents();
    }
    
    // Render color palette in modal
    function renderDeptColorPalette() {
        const container = document.getElementById('deptEventColorPalette');
        if (!container) return;
        
        container.innerHTML = deptColorPalette.map(c => `
            <div class="color-swatch ${c.color === '#667eea' ? 'selected' : ''}" 
                 style="background: ${c.color};" 
                 title="${c.name}"
                 onclick="selectDeptEventColor('${c.color}', this)">
            </div>
        `).join('');
    }
    
    // Select a color for the event
    function selectDeptEventColor(color, element) {
        document.getElementById('deptEventColor').value = color;
        document.querySelectorAll('.color-swatch').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
    }
    
    // Load department events from API
    async function loadDeptEvents() {
        const year = deptCalendarDate.getFullYear();
        const month = deptCalendarDate.getMonth();
        
        // Get a range that covers the visible calendar (including overflow days)
        const startDate = new Date(year, month - 1, 1).toISOString().split('T')[0];
        const endDate = new Date(year, month + 2, 0).toISOString().split('T')[0];
        
        try {
            const response = await fetch(`${API_URL}?action=getDeptEvents&start=${startDate}&end=${endDate}`);
            const data = await response.json();
            
            if (data.success) {
                deptEvents = data.events || [];
            } else {
                console.error('API error:', data.error);
                deptEvents = [];
            }
        } catch (error) {
            console.error('Error loading department events:', error);
            deptEvents = [];
        }
        
        // Always render the calendar
        renderDeptCalendar();
    }
    
    // Render the department calendar based on current view
    function renderDeptCalendar() {
        const container = document.getElementById('deptCalendarGrid');
        if (!container) return;
        
        // Update title
        const titleEl = document.getElementById('deptCalendarTitle');
        if (titleEl) {
            const options = { year: 'numeric', month: 'long' };
            if (deptCalendarView === 'week') {
                const weekStart = getWeekStart(deptCalendarDate);
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);
                titleEl.textContent = `${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`;
            } else if (deptCalendarView === 'day') {
                titleEl.textContent = deptCalendarDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            } else {
                titleEl.textContent = deptCalendarDate.toLocaleDateString('en-US', options);
            }
        }
        
        switch (deptCalendarView) {
            case 'week':
                renderDeptWeekView(container);
                break;
            case 'day':
                renderDeptDayView(container);
                break;
            default:
                renderDeptMonthView(container);
        }
    }
    
    // Render month view
    function renderDeptMonthView(container) {
        const year = deptCalendarDate.getFullYear();
        const month = deptCalendarDate.getMonth();
        const firstDay = new Date(year, month, 1).getDay(); // 0=Sunday, 1=Monday, etc.
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        const today = new Date();
        
        // Helper to format date as YYYY-MM-DD without timezone issues
        const formatDate = (y, m, d) => {
            return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        };
        
        let html = `
            <div class="dept-calendar-header">
                <div class="dept-calendar-header-cell">Sun</div>
                <div class="dept-calendar-header-cell">Mon</div>
                <div class="dept-calendar-header-cell">Tue</div>
                <div class="dept-calendar-header-cell">Wed</div>
                <div class="dept-calendar-header-cell">Thu</div>
                <div class="dept-calendar-header-cell">Fri</div>
                <div class="dept-calendar-header-cell">Sat</div>
            </div>
            <div class="dept-calendar-days">
        `;
        
        // Previous month days (fill cells before the 1st of current month)
        const prevMonth = month === 0 ? 11 : month - 1;
        const prevYear = month === 0 ? year - 1 : year;
        for (let i = 0; i < firstDay; i++) {
            const day = daysInPrevMonth - firstDay + 1 + i;
            const dateStr = formatDate(prevYear, prevMonth, day);
            html += `<div class="dept-calendar-day other-month" onclick="openDeptEventModal('${dateStr}')">
                <div class="dept-day-number">${day}</div>
                ${renderDeptEventsForDay(dateStr)}
            </div>`;
        }
        
        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = formatDate(year, month, day);
            const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
            
            html += `<div class="dept-calendar-day ${isToday ? 'today' : ''}" onclick="openDeptEventModal('${dateStr}')">
                <div class="dept-day-number">${day}</div>
                ${renderDeptEventsForDay(dateStr)}
            </div>`;
        }
        
        // Next month days (fill remaining cells to complete the grid)
        const totalCells = firstDay + daysInMonth;
        const remainingCells = totalCells <= 35 ? 35 - totalCells : 42 - totalCells;
        const nextMonth = month === 11 ? 0 : month + 1;
        const nextYear = month === 11 ? year + 1 : year;
        for (let day = 1; day <= remainingCells; day++) {
            const dateStr = formatDate(nextYear, nextMonth, day);
            html += `<div class="dept-calendar-day other-month" onclick="openDeptEventModal('${dateStr}')">
                <div class="dept-day-number">${day}</div>
                ${renderDeptEventsForDay(dateStr)}
            </div>`;
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // Render events for a specific day
    function renderDeptEventsForDay(dateStr) {
        const dayEvents = deptEvents.filter(e => {
            const eventDate = e.instance_date || e.start_date;
            return eventDate === dateStr;
        });
        
        return dayEvents.slice(0, 3).map(e => `
            <div class="dept-event" style="background: ${e.event_color || '#667eea'};" 
                 onclick="event.stopPropagation(); editDeptEvent(${e.id}, '${e.instance_date || e.start_date}', ${e.is_recurring || false})"
                 title="${e.title}">
                ${e.is_recurring && !e.is_instance ? '🔄 ' : ''}${e.title}
            </div>
        `).join('') + (dayEvents.length > 3 ? `<div style="font-size: 0.7rem; color: #6c757d; padding: 2px;">+${dayEvents.length - 3} more</div>` : '');
    }
    
    // Helper to format date as YYYY-MM-DD without timezone issues
    function formatDateLocal(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }
    
    // Render week view (simplified for all-day events)
    function renderDeptWeekView(container) {
        const weekStart = getWeekStart(deptCalendarDate);
        const today = new Date();
        const todayStr = formatDateLocal(today);
        
        let html = '<div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e0e0e0; border-radius: 8px; overflow: hidden;">';
        
        // Header row with days
        for (let i = 0; i < 7; i++) {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + i);
            const dateStr = formatDateLocal(date);
            const isToday = dateStr === todayStr;
            
            const dayEvents = deptEvents.filter(e => (e.instance_date || e.start_date) === dateStr);
            
            html += `<div style="background: white; min-height: 200px; padding: 10px;" onclick="openDeptEventModal('${dateStr}')">
                <div style="text-align: center; padding: 8px; margin: -10px -10px 10px -10px; ${isToday ? 'background: #667eea; color: white;' : 'background: #f8f9fa;'}">
                    <div style="font-weight: 600;">${date.toLocaleDateString('en-US', { weekday: 'short' })}</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">${date.getDate()}</div>
                </div>
                ${dayEvents.map(e => `
                    <div class="dept-event" style="background: ${e.event_color || '#667eea'}; margin-bottom: 4px;" 
                         onclick="event.stopPropagation(); editDeptEvent(${e.id}, '${dateStr}', ${e.is_recurring || false})"
                         title="${e.title}">
                        ${e.is_recurring ? '🔄 ' : ''}${e.title.length > 20 ? e.title.substring(0, 20) + '...' : e.title}
                    </div>
                `).join('')}
            </div>`;
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    // Render day view (simplified for all-day events)
    function renderDeptDayView(container) {
        const dateStr = formatDateLocal(deptCalendarDate);
        const dayEvents = deptEvents.filter(e => (e.instance_date || e.start_date) === dateStr);
        
        let html = `
            <div style="background: white; border-radius: 8px; overflow: hidden;">
                <div style="background: #667eea; color: white; padding: 20px; text-align: center;">
                    <div style="font-size: 1.2rem;">${deptCalendarDate.toLocaleDateString('en-US', { weekday: 'long' })}</div>
                    <div style="font-size: 2.5rem; font-weight: bold;">${deptCalendarDate.getDate()}</div>
                    <div>${deptCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</div>
                </div>
                <div style="padding: 20px; min-height: 300px;" onclick="openDeptEventModal('${dateStr}')">
                    ${dayEvents.length === 0 ? '<p style="color: #6c757d; text-align: center;">No events scheduled. Click to add one.</p>' : ''}
                    ${dayEvents.map(e => `
                        <div class="dept-event" style="background: ${e.event_color || '#667eea'}; padding: 12px 15px; margin-bottom: 10px; border-radius: 8px; font-size: 1rem;" 
                             onclick="event.stopPropagation(); editDeptEvent(${e.id}, '${dateStr}', ${e.is_recurring || false})">
                            <div style="font-weight: 600;">${e.is_recurring ? '🔄 ' : ''}${e.title}</div>
                            ${e.description ? `<div style="font-size: 0.85rem; opacity: 0.9; margin-top: 5px;">${e.description}</div>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        container.innerHTML = html;
    }
    
    // Get the start of the week (Sunday)
    function getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        d.setDate(d.getDate() - day);
        return d;
    }
    
    // Navigate calendar
    function navigateDeptCalendar(direction) {
        switch (deptCalendarView) {
            case 'week':
                deptCalendarDate.setDate(deptCalendarDate.getDate() + (direction * 7));
                break;
            case 'day':
                deptCalendarDate.setDate(deptCalendarDate.getDate() + direction);
                break;
            default:
                deptCalendarDate.setMonth(deptCalendarDate.getMonth() + direction);
        }
        loadDeptEvents();
    }
    
    // Navigate to today
    function navigateDeptCalendarToday() {
        deptCalendarDate = new Date();
        loadDeptEvents();
    }
    
    // Set calendar view
    function setDeptCalendarView(view) {
        deptCalendarView = view;
        document.querySelectorAll('.dept-view-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('btn-secondary');
        });
        document.querySelector(`.dept-view-btn[data-view="${view}"]`)?.classList.add('active');
        document.querySelector(`.dept-view-btn[data-view="${view}"]`)?.classList.remove('btn-secondary');
        renderDeptCalendar();
    }
    
    // Open modal to add/edit event
    function openDeptEventModal(dateStr = null) {
        const modal = document.getElementById('deptEventModal');
        const form = document.getElementById('deptEventForm');
        form.reset();
        
        document.getElementById('deptEventModalTitle').textContent = '➕ Add Department Event';
        document.getElementById('deptEventId').value = '';
        document.getElementById('deptEventParentId').value = '';
        document.getElementById('deptEventOriginalDate').value = '';
        document.getElementById('deptEventDeleteBtn').style.display = 'none';
        document.getElementById('deptEventCreatedBy').textContent = currentDisplayName || currentUsername || 'You';
        
        // Reset color selection
        document.getElementById('deptEventColor').value = '#667eea';
        document.querySelectorAll('.color-swatch').forEach(el => el.classList.remove('selected'));
        document.querySelector('.color-swatch')?.classList.add('selected');
        
        // Set date if provided
        if (dateStr) {
            document.getElementById('deptEventStartDate').value = dateStr;
            document.getElementById('deptEventEndDate').value = dateStr;
        } else {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('deptEventStartDate').value = today;
            document.getElementById('deptEventEndDate').value = today;
        }
        
        // Set default recurrence end date (1 month from start)
        const startDate = new Date(document.getElementById('deptEventStartDate').value);
        startDate.setMonth(startDate.getMonth() + 1);
        document.getElementById('deptEventRecurrenceEndDate').value = startDate.toISOString().split('T')[0];
        
        // Reset UI state
        document.getElementById('deptEventRecurring').checked = false;
        toggleDeptRecurrenceOptions();
        
        modal.style.display = 'block';
    }
    
    // Close modal
    function closeDeptEventModal() {
        document.getElementById('deptEventModal').style.display = 'none';
        currentDeptEvent = null;
    }
    
    // Toggle recurrence options
    function toggleDeptRecurrenceOptions() {
        const isRecurring = document.getElementById('deptEventRecurring').checked;
        document.getElementById('deptRecurrenceOptions').style.display = isRecurring ? 'block' : 'none';
    }
    
    // Toggle recurrence end fields
    function toggleDeptRecurrenceEndFields() {
        const endType = document.querySelector('input[name="deptRecurrenceEnd"]:checked').value;
        document.getElementById('deptEventRecurrenceEndDate').disabled = endType !== 'date';
        document.getElementById('deptEventRecurrenceCount').disabled = endType !== 'count';
    }
    
    // Edit an existing event
    async function editDeptEvent(eventId, instanceDate, isRecurring) {
        currentDeptEvent = { id: eventId, instanceDate, isRecurring };
        
        if (isRecurring) {
            // Show recurring edit options modal
            document.getElementById('recurringEditModal').style.display = 'block';
            return;
        }
        
        // Load and show the event
        await loadDeptEventForEdit(eventId);
    }
    
    // Handle recurring edit option selection
    async function editRecurringOption(option) {
        closeRecurringEditModal();
        
        if (!currentDeptEvent) return;
        
        pendingRecurringAction = option;
        
        if (option === 'single') {
            // Edit single instance - load parent event but set as instance edit
            await loadDeptEventForEdit(currentDeptEvent.id, currentDeptEvent.instanceDate);
        } else if (option === 'future') {
            // Edit this and future - load event and set parent info
            await loadDeptEventForEdit(currentDeptEvent.id, currentDeptEvent.instanceDate, true);
        } else {
            // Edit all - load the main recurring event
            await loadDeptEventForEdit(currentDeptEvent.id);
        }
    }
    
    // Load event data into the modal for editing
    async function loadDeptEventForEdit(eventId, instanceDate = null, isFutureEdit = false) {
        try {
            const response = await fetch(`${API_URL}?action=getDeptEvent&id=${eventId}`);
            const data = await response.json();
            
            if (!data.success || !data.event) {
                showToast('Event not found.', 'warning');
                return;
            }
            
            const event = data.event;
            const modal = document.getElementById('deptEventModal');
            
            document.getElementById('deptEventModalTitle').textContent = '✏️ Edit Department Event';
            document.getElementById('deptEventDeleteBtn').style.display = 'block';
            
            // Set IDs based on edit type
            if (instanceDate && pendingRecurringAction === 'single') {
                document.getElementById('deptEventId').value = '';
                document.getElementById('deptEventParentId').value = eventId;
                document.getElementById('deptEventOriginalDate').value = instanceDate;
            } else if (isFutureEdit) {
                document.getElementById('deptEventId').value = '';
                document.getElementById('deptEventParentId').value = eventId;
                document.getElementById('deptEventOriginalDate').value = instanceDate;
            } else {
                document.getElementById('deptEventId').value = eventId;
                document.getElementById('deptEventParentId').value = '';
                document.getElementById('deptEventOriginalDate').value = '';
            }
            
            // Populate form fields
            document.getElementById('deptEventTitle').value = event.title || '';
            document.getElementById('deptEventDescription').value = event.description || '';
            document.getElementById('deptEventStartDate').value = instanceDate || event.start_date;
            document.getElementById('deptEventEndDate').value = instanceDate || event.end_date;
            
            // Set color
            document.getElementById('deptEventColor').value = event.event_color || '#667eea';
            document.querySelectorAll('.color-swatch').forEach(el => {
                el.classList.toggle('selected', el.style.background === event.event_color);
            });
            
            // Recurring settings (only for "all" edit)
            if (pendingRecurringAction === 'all' || !instanceDate) {
                document.getElementById('deptEventRecurring').checked = event.is_recurring == 1;
                if (event.is_recurring) {
                    document.getElementById('deptEventRecurrenceType').value = event.recurrence_type || 'weekly';
                    if (event.recurrence_end_type === 'count') {
                        document.getElementById('deptRecurrenceEndCount').checked = true;
                        document.getElementById('deptEventRecurrenceCount').value = event.recurrence_count || 10;
                    } else {
                        document.getElementById('deptRecurrenceEndDate').checked = true;
                        document.getElementById('deptEventRecurrenceEndDate').value = event.recurrence_end_date || '';
                    }
                }
            } else {
                document.getElementById('deptEventRecurring').checked = false;
            }
            
            document.getElementById('deptEventCreatedBy').textContent = event.createdByDisplayName || event.createdByUsername || 'Unknown';
            
            toggleDeptRecurrenceOptions();
            toggleDeptRecurrenceEndFields();
            
            modal.style.display = 'block';
            
        } catch (error) {
            console.error('Error loading event:', error);
            showToast('Error loading event details', 'error');
        }
    }
    
    // Save department event
    async function saveDeptEvent(e) {
        e.preventDefault();
        
        const eventId = document.getElementById('deptEventId').value;
        const parentId = document.getElementById('deptEventParentId').value;
        const originalDate = document.getElementById('deptEventOriginalDate').value;
        const isRecurring = document.getElementById('deptEventRecurring').checked;
        
        const eventData = {
            title: document.getElementById('deptEventTitle').value,
            description: document.getElementById('deptEventDescription').value,
            event_color: document.getElementById('deptEventColor').value,
            start_date: document.getElementById('deptEventStartDate').value,
            end_date: document.getElementById('deptEventEndDate').value,
            is_recurring: isRecurring,
            recurrence_type: isRecurring ? document.getElementById('deptEventRecurrenceType').value : null,
            recurrence_end_type: isRecurring ? document.querySelector('input[name="deptRecurrenceEnd"]:checked').value : null,
            recurrence_end_date: isRecurring && document.getElementById('deptRecurrenceEndDate').checked ? document.getElementById('deptEventRecurrenceEndDate').value : null,
            recurrence_count: isRecurring && document.getElementById('deptRecurrenceEndCount').checked ? document.getElementById('deptEventRecurrenceCount').value : null
        };
        
        let action, body;
        
        if (parentId && pendingRecurringAction === 'single') {
            // Save as single instance exception
            action = 'saveDeptEventInstance';
            body = { ...eventData, parent_event_id: parentId, original_date: originalDate };
        } else if (parentId && pendingRecurringAction === 'future') {
            // Update this and all future instances
            action = 'updateFutureInstances';
            body = { ...eventData, parent_event_id: parentId, from_date: originalDate };
        } else {
            // Normal save (new or edit all)
            action = 'saveDeptEvent';
            body = { ...eventData, id: eventId || null };
        }
        
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            
            const result = await response.json();
            
            if (result.success) {
                closeDeptEventModal();
                pendingRecurringAction = null;
                loadDeptEvents();
            } else {
                showToast('Error saving event: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving event:', error);
            showToast('Error saving event', 'error');
        }
    }
    
    // Delete event
    function confirmDeleteDeptEvent() {
        if (currentDeptEvent?.isRecurring || document.getElementById('deptEventRecurring').checked) {
            document.getElementById('recurringDeleteModal').style.display = 'block';
        } else {
            if (confirm('Are you sure you want to delete this event?')) {
                deleteDeptEvent();
            }
        }
    }
    
    async function deleteDeptEvent() {
        const eventId = document.getElementById('deptEventId').value;
        if (!eventId) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteDeptEvent&id=${eventId}`, { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                closeDeptEventModal();
                loadDeptEvents();
            } else {
                showToast('Error deleting event: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting event:', error);
            showToast('Error deleting event', 'error');
        }
    }
    
    // Handle recurring delete option
    async function deleteRecurringOption(option) {
        closeRecurringDeleteModal();
        
        if (option === 'single') {
            const parentId = document.getElementById('deptEventParentId').value || document.getElementById('deptEventId').value;
            const originalDate = document.getElementById('deptEventOriginalDate').value || currentDeptEvent?.instanceDate;
            
            try {
                const response = await fetch(`${API_URL}?action=deleteDeptEventInstance`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ parent_event_id: parentId, original_date: originalDate })
                });
                
                const result = await response.json();
                if (result.success) {
                    closeDeptEventModal();
                    loadDeptEvents();
                } else {
                    showToast('Error deleting instance: ' + (result.error || 'Unknown error', 'error'));
                }
            } catch (error) {
                console.error('Error deleting instance:', error);
            }
        } else {
            // Delete all instances
            await deleteDeptEvent();
        }
    }
    
    // Close recurring modals
    function closeRecurringEditModal() {
        document.getElementById('recurringEditModal').style.display = 'none';
    }
    
    function closeRecurringDeleteModal() {
        document.getElementById('recurringDeleteModal').style.display = 'none';
    }
    
    // =============================================
    // COMPANIES & COMPANY CONTACTS FUNCTIONS
    // =============================================
    
    // Sub-tab navigation for Contacts section
    function showContactSubTab(tabName) {
        // Hide all sub-tab contents
        document.querySelectorAll('.sub-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Remove active from all sub-tabs
        document.querySelectorAll('.sub-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show the selected sub-tab content
        const contentMap = {
            'federal': 'federalContactsSubTab',
            'agencies': 'agenciesSubTab',
            'companies': 'companiesSubTab',
            'companyContacts': 'companyContactsSubTab'
        };
        
        document.getElementById(contentMap[tabName])?.classList.add('active');
        
        // Mark the clicked tab as active
        event.target.classList.add('active');
    }
    
    // Populate Companies Table
    function populateCompaniesTable() {
        const tbody = document.getElementById('companiesTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = companies.map(company => {
            const sbStatuses = companySmallBusinessStatuses[company.id] || [];
            const vehicles = companyVehicles[company.id] || [];
            
            const sbBadges = sbStatuses.map(s => `<span class="company-badge sbs">${s}</span>`).join('');
            const vehBadges = vehicles.map(v => `<span class="company-badge vehicle">${v}</span>`).join('');
            
            let postureBadge = '';
            if (company.competitive_posture === 'Partner') {
                postureBadge = '<span class="company-badge posture-partner">Partner</span>';
            } else if (company.competitive_posture === 'Competitor') {
                postureBadge = '<span class="company-badge posture-competitor">Competitor</span>';
            } else if (company.competitive_posture === 'Partner-Competitor') {
                postureBadge = '<span class="company-badge posture-both">Partner-Competitor</span>';
            }
            
            let strategicBadge = '';
            if (company.strategic_importance === 'Anchor Partner') {
                strategicBadge = '<span class="company-badge strategic-anchor">⭐ Anchor</span>';
            } else if (company.strategic_importance === 'Strategic') {
                strategicBadge = '<span class="company-badge strategic-strategic">Strategic</span>';
            } else if (company.strategic_importance === 'Opportunistic') {
                strategicBadge = '<span class="company-badge strategic-opportunistic">Opportunistic</span>';
            }
            
            return `
                <tr>
                    <td><a href="javascript:void(0)" onclick="openCompanyPanel(${company.id})" class="contact-name-link"><strong>${company.company_name || ''}</strong></a></td>
                    <td>${company.company_type || '—'}</td>
                    <td>${company.parentCompanyName || '—'}</td>
                    <td>${strategicBadge || '—'}</td>
                    <td>${postureBadge || '—'}</td>
                    <td>${sbBadges || '—'}</td>
                    <td>${vehBadges || '—'}</td>
                    <td>${company.contactCount || 0}</td>
                    <td>${getCompanyActionsHtml(company.id)}</td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="9" class="empty-state">No companies found.</td></tr>';
    }
    
    function getCompanyActionsHtml(id) {
        let html = '<div class="action-buttons">';
        if (userPermissions.contact?.can_update) {
            html += `<button class="action-btn edit" onclick="editCompany(${id})">Edit</button>`;
        }
        if (userPermissions.contact?.can_delete) {
            html += `<button class="action-btn delete" onclick="deleteItem('company', ${id})">Delete</button>`;
        }
        return html + '</div>';
    }
    
    // Populate Company Contacts Table
    function populateCompanyContactsTable() {
        const tbody = document.getElementById('companyContactsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = companyContacts.map(cc => {
            // Get primary owner display name
            const primaryOwnerName = cc.primaryOwnerDisplayName || cc.primaryOwnerUsername || '—';
            
            // Get secondary owners
            const secondaryOwners = companyContactSecondaryOwners[cc.id] || [];
            const secondaryOwnersHtml = secondaryOwners.length > 0
                ? secondaryOwners.map(o => `<span class="owner-badge-sm">👤 ${o.display_name || o.username}</span>`).join(' ')
                : '—';
            
            return `
                <tr>
                    <td><a href="javascript:void(0)" onclick="openCompanyContactPanel(${cc.id})" class="contact-name-link">${cc.first_name || ''} ${cc.last_name || ''}</a></td>
                    <td>${cc.title || '—'}</td>
                    <td>${cc.companyName || '—'}</td>
                    <td>${cc.primary_owner_id ? `<span class="owner-badge">👤 ${primaryOwnerName}</span>` : '—'}</td>
                    <td>${secondaryOwnersHtml}</td>
                    <td>${cc.functional_role || '—'}</td>
                    <td>${cc.capture_role || '—'}</td>
                    <td>${cc.email || '—'}</td>
                    <td><span class="status-badge status-${(cc.status || 'active').toLowerCase()}">${cc.status || 'Active'}</span></td>
                    <td>${getCompanyContactActionsHtml(cc.id)}</td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="10" class="empty-state">No company contacts found.</td></tr>';
    }
    
    function getCompanyContactActionsHtml(id) {
        let html = '<div class="action-buttons">';
        
        // Specialty users cannot edit or delete
        if (!window.isSpecialty) {
            if (userPermissions.contact?.can_update) {
                html += `<button class="action-btn edit" onclick="editCompanyContact(${id})">Edit</button>`;
            }
            if (userPermissions.contact?.can_delete) {
                html += `<button class="action-btn delete" onclick="deleteItem('company_contact', ${id})">Delete</button>`;
            }
        }
        
        return html + '</div>';
    }
    
    // Populate company filters
    function populateCompanyFilters() {
        const companyFilter = document.getElementById('companyContactCompanyFilter');
        if (companyFilter) {
            const options = companies.map(c => `<option value="${c.id}">${c.company_name}</option>`).join('');
            companyFilter.innerHTML = '<option value="ALL">All Companies</option>' + options;
        }
    }
    
    // Filter Companies
    function filterCompanies() {
        const search = (document.getElementById('companySearch')?.value || '').toLowerCase();
        const typeFilter = document.getElementById('companyTypeFilter')?.value || 'ALL';
        const postureFilter = document.getElementById('companyPostureFilter')?.value || 'ALL';
        
        document.querySelectorAll('#companiesTableBody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = text.includes(search);
            const matchesType = typeFilter === 'ALL' || text.includes(typeFilter.toLowerCase());
            const matchesPosture = postureFilter === 'ALL' || text.includes(postureFilter.toLowerCase());
            
            row.style.display = matchesSearch && matchesType && matchesPosture ? '' : 'none';
        });
    }
    
    // Filter Company Contacts
    function filterCompanyContacts() {
        const search = (document.getElementById('companyContactSearch')?.value || '').toLowerCase();
        const companyFilter = document.getElementById('companyContactCompanyFilter')?.value || 'ALL';
        const roleFilter = document.getElementById('companyContactRoleFilter')?.value || 'ALL';
        
        document.querySelectorAll('#companyContactsTableBody tr').forEach((row, index) => {
            const cc = companyContacts[index];
            if (!cc) return;
            
            const text = row.textContent.toLowerCase();
            const matchesSearch = text.includes(search);
            const matchesCompany = companyFilter === 'ALL' || cc.company_id == companyFilter;
            const matchesRole = roleFilter === 'ALL' || cc.functional_role === roleFilter;
            
            row.style.display = matchesSearch && matchesCompany && matchesRole ? '' : 'none';
        });
    }
    
    // =============================================
    // COMPANY MODAL FUNCTIONS
    // =============================================
    
    // Parent company search
    let parentCompanySearchTimeout = null;
    
    function filterParentCompanies() {
        clearTimeout(parentCompanySearchTimeout);
        parentCompanySearchTimeout = setTimeout(() => {
            const search = document.getElementById('parentCompanySearch').value.toLowerCase();
            const dropdown = document.getElementById('parentCompanyDropdown');
            
            if (!search) {
                dropdown.style.display = 'none';
                document.getElementById('parentCompanyPrompt').style.display = 'none';
                return;
            }
            
            const currentCompanyId = document.getElementById('companyId').value;
            const filtered = companies.filter(c => 
                c.company_name.toLowerCase().includes(search) && c.id != currentCompanyId
            );
            
            if (filtered.length > 0) {
                dropdown.innerHTML = filtered.map(c => `
                    <div class="search-select-option" onclick="selectParentCompany(${c.id}, '${c.company_name.replace(/'/g, "\\'")}')">
                        ${c.company_name}
                    </div>
                `).join('');
                dropdown.style.display = 'block';
                document.getElementById('parentCompanyPrompt').style.display = 'none';
            } else {
                dropdown.style.display = 'none';
                // Show prompt to create new company
                document.getElementById('newParentCompanyName').textContent = document.getElementById('parentCompanySearch').value;
                document.getElementById('parentCompanyPrompt').style.display = 'block';
            }
        }, 300);
    }
    
    function showParentCompanyDropdown() {
        const search = document.getElementById('parentCompanySearch').value.toLowerCase();
        if (search) {
            filterParentCompanies();
        }
    }
    
    function selectParentCompany(id, name) {
        document.getElementById('parentCompanyId').value = id;
        document.getElementById('parentCompanySearch').value = '';
        document.getElementById('parentCompanyDropdown').style.display = 'none';
        document.getElementById('selectedParentCompany').style.display = 'block';
        document.getElementById('selectedParentCompany').innerHTML = `
            <span>${name}</span>
            <button type="button" onclick="clearParentCompany()" style="background: none; border: none; cursor: pointer; color: #dc3545;">&times;</button>
        `;
    }
    
    function clearParentCompany() {
        document.getElementById('parentCompanyId').value = '';
        document.getElementById('selectedParentCompany').style.display = 'none';
        document.getElementById('parentCompanySearch').value = '';
    }
    
    async function createParentCompany() {
        const newCompanyName = document.getElementById('parentCompanySearch').value;
        if (!newCompanyName) return;
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompany`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ company_name: newCompanyName, status: 'Active' })
            });
            const data = await response.json();
            
            if (data.success) {
                // Refresh companies list
                await fetchAllData();
                // Select the new company as parent
                selectParentCompany(data.id, newCompanyName);
                document.getElementById('parentCompanyPrompt').style.display = 'none';
                showToast(`Company "${newCompanyName}" created successfully!`, 'success');
            } else {
                showToast('Error creating company: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error creating parent company:', error);
            showToast('Error creating company. Please try again.', 'error');
        }
    }
    
    function cancelParentCompanyPrompt() {
        document.getElementById('parentCompanyPrompt').style.display = 'none';
        document.getElementById('parentCompanySearch').value = '';
    }
    
    // =============================================
    // CORE FEDERAL CUSTOMERS MULTI-SELECT
    // =============================================
    
    function filterCompanyCoreCustomers() {
        const search = (document.getElementById('companyCoreCustomerSearch')?.value || '').toLowerCase();
        renderCompanyCoreCustomerList(search);
    }
    
    function renderCompanyCoreCustomerList(search = '') {
        const container = document.getElementById('companyCoreCustomerList');
        if (!container) return;
        
        const filtered = agencies.filter(a => 
            !search || a.name.toLowerCase().includes(search)
        );
        
        container.innerHTML = filtered.map(agency => {
            const agencyId = parseInt(agency.id);
            const isSelected = companySelectedCoreCustomerIds.includes(agencyId);
            return `
                <div class="agency-select-item ${isSelected ? 'selected' : ''}" onclick="toggleCompanyCoreCustomer(${agencyId})">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleCompanyCoreCustomer(${agencyId})">
                    <span>${agency.name}</span>
                </div>
            `;
        }).join('') || '<div style="padding: 10px; color: #999;">No agencies found</div>';
    }
    
    function toggleCompanyCoreCustomer(agencyId) {
        agencyId = parseInt(agencyId);
        const index = companySelectedCoreCustomerIds.indexOf(agencyId);
        if (index > -1) {
            companySelectedCoreCustomerIds.splice(index, 1);
        } else {
            companySelectedCoreCustomerIds.push(agencyId);
        }
        renderCompanyCoreCustomerList(document.getElementById('companyCoreCustomerSearch')?.value || '');
        renderCompanySelectedCoreCustomers();
    }
    
    function renderCompanySelectedCoreCustomers() {
        const container = document.getElementById('companySelectedCoreCustomers');
        if (!container) return;
        
        if (companySelectedCoreCustomerIds.length === 0) {
            container.innerHTML = '<span style="color: #999; font-size: 0.9rem;">No agencies selected</span>';
            return;
        }
        
        container.innerHTML = companySelectedCoreCustomerIds.map(id => {
            const agency = agencies.find(a => parseInt(a.id) === id);
            return agency ? `
                <span class="company-contact-agency-tag">
                    ${agency.name}
                    <button type="button" onclick="toggleCompanyCoreCustomer(${id})" style="background: none; border: none; cursor: pointer; margin-left: 5px; color: #1565c0;">&times;</button>
                </span>
            ` : '';
        }).join('');
    }
    
    // =============================================
    // CONTRACT VEHICLES MANAGEMENT
    // =============================================
    
    const predefinedVehicles = ['GSA MAS', 'SeaPort-NxG', 'CIO-SP4', 'OASIS Plus', 'Agency-Specific IDIQs'];
    
    function addCustomVehicle() {
        const input = document.getElementById('customVehicleInput');
        const value = input.value.trim();
        
        if (!value) return;
        
        // Check if it's already in predefined list
        if (predefinedVehicles.includes(value)) {
            // Just check the checkbox
            const checkbox = document.querySelector(`input[name="vehicles"][value="${value}"]`);
            if (checkbox) checkbox.checked = true;
        } else if (!companyCustomVehicles.includes(value)) {
            // Add to custom vehicles
            companyCustomVehicles.push(value);
        }
        
        input.value = '';
        updateVehiclesTags();
    }
    
    function updateVehiclesTags() {
        const container = document.getElementById('selectedVehiclesTags');
        if (!container) return;
        
        // Get checked predefined vehicles
        const checkedVehicles = [];
        document.querySelectorAll('input[name="vehicles"]:checked').forEach(cb => {
            checkedVehicles.push(cb.value);
        });
        
        // Combine with custom vehicles
        const allVehicles = [...checkedVehicles, ...companyCustomVehicles];
        
        if (allVehicles.length === 0) {
            container.innerHTML = '<span style="color: #999; font-size: 0.9rem;">No vehicles selected</span>';
            return;
        }
        
        container.innerHTML = allVehicles.map(vehicle => {
            const isPredefined = predefinedVehicles.includes(vehicle);
            return `
                <span class="company-contact-agency-tag" style="background: #e8def8; color: #4a148c;">
                    ${vehicle}
                    <button type="button" onclick="removeVehicle('${vehicle.replace(/'/g, "\\'")}', ${isPredefined})" style="background: none; border: none; cursor: pointer; margin-left: 5px; color: #4a148c;">&times;</button>
                </span>
            `;
        }).join('');
    }
    
    function removeVehicle(vehicle, isPredefined) {
        if (isPredefined) {
            // Uncheck the checkbox
            const checkbox = document.querySelector(`input[name="vehicles"][value="${vehicle}"]`);
            if (checkbox) checkbox.checked = false;
        } else {
            // Remove from custom vehicles
            companyCustomVehicles = companyCustomVehicles.filter(v => v !== vehicle);
        }
        updateVehiclesTags();
    }
    
    function getAllSelectedVehicles() {
        const checkedVehicles = [];
        document.querySelectorAll('input[name="vehicles"]:checked').forEach(cb => {
            checkedVehicles.push(cb.value);
        });
        return [...checkedVehicles, ...companyCustomVehicles];
    }
    
    // Company form submit
    document.getElementById('companyForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Gather small business statuses
        const smallBusinessStatuses = [];
        document.querySelectorAll('input[name="smallBusinessStatus"]:checked').forEach(cb => {
            smallBusinessStatuses.push(cb.value);
        });
        
        // Gather vehicles (predefined + custom)
        const vehicles = getAllSelectedVehicles();
        
        const companyData = {
            id: document.getElementById('companyId').value || null,
            company_name: document.getElementById('companyName').value,
            company_type: document.getElementById('companyType').value || null,
            parent_company_id: document.getElementById('parentCompanyId').value || null,
            website: document.getElementById('companyWebsite').value || null,
            description: document.getElementById('companyDescription').value || null,
            primary_naics_codes: document.getElementById('companyNaicsCodes').value || null,
            uei: document.getElementById('companyUei').value || null,
            cage_code: document.getElementById('companyCageCode').value || null,
            strategic_importance: document.getElementById('companyStrategicImportance').value || null,
            competitive_posture: document.getElementById('companyCompetitivePosture').value || null,
            status: document.getElementById('companyStatus').value,
            small_business_statuses: smallBusinessStatuses,
            vehicles: vehicles,
            core_customers: companySelectedCoreCustomerIds
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompany`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(companyData)
            });
            const data = await response.json();
            
            if (data.success) {
                closeModal('companyModal');
                await fetchAllData();
            } else {
                showToast('Error saving company: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving company:', error);
            showToast('Error saving company. Please try again.', 'error');
        }
    });
    
    // Edit company
    async function editCompany(id) {
        try {
            const response = await fetch(`${API_URL}?action=getCompanyDetail&id=${id}`);
            const data = await response.json();
            
            if (data.success && data.company) {
                const company = data.company;
                
                // Set currentEditId BEFORE setting form values to prevent form reset
                currentEditId = company.id;
                
                document.getElementById('companyModalTitle').textContent = 'Edit Company';
                document.getElementById('companyId').value = company.id;
                document.getElementById('companyName').value = company.company_name || '';
                document.getElementById('companyType').value = company.company_type || '';
                document.getElementById('companyWebsite').value = company.website || '';
                document.getElementById('companyDescription').value = company.description || '';
                document.getElementById('companyNaicsCodes').value = company.primary_naics_codes || '';
                document.getElementById('companyUei').value = company.uei || '';
                document.getElementById('companyCageCode').value = company.cage_code || '';
                document.getElementById('companyStrategicImportance').value = company.strategic_importance || '';
                document.getElementById('companyCompetitivePosture').value = company.competitive_posture || '';
                document.getElementById('companyStatus').value = company.status || 'Active';
                
                // Parent company
                if (company.parent_company_id) {
                    selectParentCompany(company.parent_company_id, company.parentCompanyName || '');
                } else {
                    clearParentCompany();
                }
                
                // Small business statuses
                document.querySelectorAll('input[name="smallBusinessStatus"]').forEach(cb => {
                    cb.checked = company.small_business_statuses?.includes(cb.value) || false;
                });
                
                // Vehicles - separate predefined from custom
                companyCustomVehicles = [];
                document.querySelectorAll('input[name="vehicles"]').forEach(cb => {
                    cb.checked = company.vehicles?.includes(cb.value) || false;
                });
                // Add any custom vehicles (not in predefined list)
                if (company.vehicles) {
                    company.vehicles.forEach(v => {
                        if (!predefinedVehicles.includes(v)) {
                            companyCustomVehicles.push(v);
                        }
                    });
                }
                updateVehiclesTags();
                
                // Core Federal Customers - ensure IDs are integers
                companySelectedCoreCustomerIds = company.core_customers?.map(c => parseInt(c.agency_id)) || [];
                renderCompanyCoreCustomerList();
                renderCompanySelectedCoreCustomers();
                
                openModal('companyModal', true);
            }
        } catch (error) {
            console.error('Error loading company:', error);
            showToast('Error loading company details.', 'error');
        }
    }
    
    // =============================================
    // COMPANY CONTACT MODAL FUNCTIONS
    // =============================================
    
    // Company search for company contacts
    function filterCCCompanies() {
        const search = document.getElementById('ccCompanySearch').value.toLowerCase();
        const dropdown = document.getElementById('ccCompanyDropdown');
        
        if (!search) {
            dropdown.style.display = 'none';
            document.getElementById('ccCompanyPrompt').style.display = 'none';
            return;
        }
        
        const filtered = companies.filter(c => c.company_name.toLowerCase().includes(search));
        
        if (filtered.length > 0) {
            dropdown.innerHTML = filtered.map(c => `
                <div class="search-select-option" onclick="selectCCCompany(${c.id}, '${c.company_name.replace(/'/g, "\\'")}')">
                    ${c.company_name}
                </div>
            `).join('');
            dropdown.style.display = 'block';
            document.getElementById('ccCompanyPrompt').style.display = 'none';
        } else {
            dropdown.style.display = 'none';
            // Show prompt to create new company
            document.getElementById('newCCCompanyName').textContent = document.getElementById('ccCompanySearch').value;
            document.getElementById('ccCompanyPrompt').style.display = 'block';
        }
    }
    
    function showCCCompanyDropdown() {
        const search = document.getElementById('ccCompanySearch').value;
        if (search) {
            filterCCCompanies();
        }
    }
    
    function selectCCCompany(id, name) {
        document.getElementById('ccCompanyId').value = id;
        document.getElementById('ccCompanySearch').value = '';
        document.getElementById('ccCompanyDropdown').style.display = 'none';
        document.getElementById('ccCompanyPrompt').style.display = 'none';
        document.getElementById('selectedCCCompany').style.display = 'block';
        document.getElementById('selectedCCCompany').innerHTML = `
            <span>${name}</span>
            <button type="button" onclick="clearCCCompany()" style="background: none; border: none; cursor: pointer; color: #dc3545;">&times;</button>
        `;
    }
    
    function clearCCCompany() {
        document.getElementById('ccCompanyId').value = '';
        document.getElementById('selectedCCCompany').style.display = 'none';
        document.getElementById('ccCompanySearch').value = '';
        document.getElementById('ccCompanyPrompt').style.display = 'none';
    }
    
    async function createCCCompany() {
        const newCompanyName = document.getElementById('ccCompanySearch').value;
        if (!newCompanyName) return;
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompany`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ company_name: newCompanyName, status: 'Active' })
            });
            const data = await response.json();
            
            if (data.success) {
                // Refresh companies list
                await fetchAllData();
                // Select the new company
                selectCCCompany(data.id, newCompanyName);
                showToast(`Company "${newCompanyName}" created successfully!`, 'success');
            } else {
                showToast('Error creating company: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error creating company:', error);
            showToast('Error creating company. Please try again.', 'error');
        }
    }
    
    function cancelCCCompanyPrompt() {
        document.getElementById('ccCompanyPrompt').style.display = 'none';
        document.getElementById('ccCompanySearch').value = '';
    }
    
    // Agency selection for company contacts
    function filterCCAgencies() {
        const search = (document.getElementById('ccAgencySearch')?.value || '').toLowerCase();
        renderCCAgencyList(search);
    }
    
    function renderCCAgencyList(search = '') {
        const container = document.getElementById('ccAgencyList');
        if (!container) return;
        
        const filtered = agencies.filter(a => 
            !search || a.name.toLowerCase().includes(search)
        );
        
        container.innerHTML = filtered.map(agency => {
            const agencyId = parseInt(agency.id);
            const isSelected = ccSelectedAgencyIds.includes(agencyId);
            return `
                <div class="agency-select-item ${isSelected ? 'selected' : ''}" onclick="toggleCCAgency(${agencyId})">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleCCAgency(${agencyId})">
                    <span>${agency.name}</span>
                </div>
            `;
        }).join('') || '<div style="padding: 10px; color: #999;">No agencies found</div>';
    }
    
    function toggleCCAgency(agencyId) {
        agencyId = parseInt(agencyId);
        const index = ccSelectedAgencyIds.indexOf(agencyId);
        if (index > -1) {
            ccSelectedAgencyIds.splice(index, 1);
        } else {
            ccSelectedAgencyIds.push(agencyId);
        }
        renderCCAgencyList(document.getElementById('ccAgencySearch')?.value || '');
        renderCCSelectedAgencies();
    }
    
    function renderCCSelectedAgencies() {
        const container = document.getElementById('ccSelectedAgencies');
        if (!container) return;
        
        if (ccSelectedAgencyIds.length === 0) {
            container.innerHTML = '<span style="color: #999; font-size: 0.9rem;">No agencies selected</span>';
            return;
        }
        
        container.innerHTML = ccSelectedAgencyIds.map(id => {
            const agency = agencies.find(a => parseInt(a.id) === id);
            return agency ? `
                <span class="company-contact-agency-tag">
                    ${agency.name}
                    <button type="button" onclick="toggleCCAgency(${id})" style="background: none; border: none; cursor: pointer; margin-left: 5px; color: #1565c0;">&times;</button>
                </span>
            ` : '';
        }).join('');
    }
    
    // =============================================
    // COMPANY CONTACT OWNER FUNCTIONS
    // =============================================
    
    // Populate primary owner select
    function populateCCOwnerSelects() {
        const primaryOwnerSelect = document.getElementById('ccPrimaryOwner');
        if (primaryOwnerSelect) {
            primaryOwnerSelect.innerHTML = '<option value="">Select Primary Owner</option>' + 
                users.map(u => `<option value="${u.id}">${u.display_name || u.username}</option>`).join('');
        }
        renderCCSecondaryOwnersList();
    }
    
    // Toggle secondary owners dropdown
    function toggleSecondaryOwnersDropdown() {
        const dropdown = document.getElementById('ccSecondaryOwnersDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
    
    // Filter secondary owners in dropdown
    function filterSecondaryOwners() {
        const searchTerm = document.getElementById('ccSecondaryOwnersSearch').value.toLowerCase();
        const options = document.querySelectorAll('#ccSecondaryOwnersList .secondary-owner-option');
        options.forEach(opt => {
            const name = opt.getAttribute('data-name').toLowerCase();
            opt.style.display = name.includes(searchTerm) ? '' : 'none';
        });
    }
    
    // Render secondary owners list in dropdown
    function renderCCSecondaryOwnersList() {
        const container = document.getElementById('ccSecondaryOwnersList');
        if (!container) return;
        
        container.innerHTML = users.map(u => {
            const isSelected = ccSelectedSecondaryOwnerIds.includes(u.id);
            const displayName = u.display_name || u.username || 'Unknown';
            return `
                <label class="secondary-owner-option ${isSelected ? 'selected' : ''}" data-id="${u.id}" data-name="${displayName}">
                    <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleSecondaryOwner(${u.id})">
                    <span class="owner-name">👤 ${displayName}</span>
                </label>
            `;
        }).join('');
    }
    
    // Toggle secondary owner selection
    function toggleSecondaryOwner(userId) {
        const index = ccSelectedSecondaryOwnerIds.indexOf(userId);
        if (index > -1) {
            ccSelectedSecondaryOwnerIds.splice(index, 1);
        } else {
            ccSelectedSecondaryOwnerIds.push(userId);
        }
        renderCCSecondaryOwnersList();
        renderCCSelectedSecondaryOwners();
    }
    
    // Render selected secondary owners chips
    function renderCCSelectedSecondaryOwners() {
        const container = document.getElementById('ccSelectedSecondaryOwners');
        const placeholder = document.getElementById('ccSecondaryOwnersPlaceholder');
        
        if (!container) return;
        
        if (ccSelectedSecondaryOwnerIds.length === 0) {
            container.innerHTML = '';
            if (placeholder) placeholder.textContent = 'Select Secondary Owners...';
            return;
        }
        
        if (placeholder) placeholder.textContent = `${ccSelectedSecondaryOwnerIds.length} owner(s) selected`;
        
        container.innerHTML = ccSelectedSecondaryOwnerIds.map(id => {
            const user = users.find(u => u.id === id);
            return user ? `
                <span class="secondary-owner-chip">
                    👤 ${user.display_name || user.username}
                    <span class="remove-btn" onclick="toggleSecondaryOwner(${id})">×</span>
                </span>
            ` : '';
        }).join('');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('ccSecondaryOwnersDropdown');
        const select = document.getElementById('ccSecondaryOwnersSelect');
        if (dropdown && select && !dropdown.contains(e.target) && !select.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
    
    // Company Contact form submit
    document.getElementById('companyContactForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const companyId = document.getElementById('ccCompanyId').value;
        if (!companyId) {
            showToast('Please select a company.', 'warning');
            return;
        }
        
        const contactData = {
            id: document.getElementById('companyContactId').value || null,
            first_name: document.getElementById('ccFirstName').value,
            last_name: document.getElementById('ccLastName').value,
            title: document.getElementById('ccTitle').value || null,
            functional_role: document.getElementById('ccFunctionalRole').value || null,
            company_id: companyId,
            capture_role: document.getElementById('ccCaptureRole').value || null,
            email: document.getElementById('ccEmail').value || null,
            phone: document.getElementById('ccPhone').value || null,
            status: document.getElementById('ccStatus').value,
            notes: document.getElementById('ccNotes').value || null,
            agencies_supported: ccSelectedAgencyIds,
            primary_owner_id: document.getElementById('ccPrimaryOwner').value || null,
            secondary_owner_ids: ccSelectedSecondaryOwnerIds
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompanyContact`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(contactData)
            });
            const data = await response.json();
            
            if (data.success) {
                closeModal('companyContactModal');
                await fetchAllData();
            } else {
                showToast('Error saving contact: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving company contact:', error);
            showToast('Error saving contact. Please try again.', 'error');
        }
    });
    
    // Edit company contact
    async function editCompanyContact(id) {
        try {
            const response = await fetch(`${API_URL}?action=getCompanyContactDetail&id=${id}`);
            const data = await response.json();
            
            if (data.success && data.contact) {
                const contact = data.contact;
                
                // Set currentEditId BEFORE setting form values to prevent form reset
                currentEditId = contact.id;
                
                document.getElementById('companyContactModalTitle').textContent = 'Edit Company Contact';
                document.getElementById('companyContactId').value = contact.id;
                document.getElementById('ccFirstName').value = contact.first_name || '';
                document.getElementById('ccLastName').value = contact.last_name || '';
                document.getElementById('ccTitle').value = contact.title || '';
                document.getElementById('ccFunctionalRole').value = contact.functional_role || '';
                document.getElementById('ccCaptureRole').value = contact.capture_role || '';
                document.getElementById('ccEmail').value = contact.email || '';
                document.getElementById('ccPhone').value = contact.phone || '';
                document.getElementById('ccStatus').value = contact.status || 'Active';
                document.getElementById('ccNotes').value = contact.notes || '';
                
                // Populate owner selects first
                populateCCOwnerSelects();
                
                // Primary Owner
                document.getElementById('ccPrimaryOwner').value = contact.primary_owner_id || '';
                
                // Secondary Owners - get from global data
                ccSelectedSecondaryOwnerIds = (companyContactSecondaryOwners[contact.id] || []).map(o => parseInt(o.user_id));
                renderCCSecondaryOwnersList();
                renderCCSelectedSecondaryOwners();
                
                // Company
                if (contact.company_id) {
                    selectCCCompany(contact.company_id, contact.companyName || '');
                } else {
                    clearCCCompany();
                }
                
                // Agencies supported - ensure IDs are integers
                ccSelectedAgencyIds = contact.agencies_supported?.map(a => parseInt(a.agency_id)) || [];
                renderCCAgencyList();
                renderCCSelectedAgencies();
                
                openModal('companyContactModal', true);
            }
        } catch (error) {
            console.error('Error loading company contact:', error);
            showToast('Error loading contact details.', 'error');
        }
    }
    
    // Reset company contact modal when opening for new
    const origOpenModal = openModal;
    openModal = function(modalId, isEdit = false) {
        if (modalId === 'companyContactModal' && !isEdit) {
            document.getElementById('companyContactModalTitle').textContent = 'Add Company Contact';
            document.getElementById('companyContactForm').reset();
            document.getElementById('companyContactId').value = '';
            clearCCCompany();
            ccSelectedAgencyIds = [];
            ccSelectedSecondaryOwnerIds = [];
            renderCCAgencyList();
            renderCCSelectedAgencies();
            renderCCSecondaryOwnersList();
            renderCCSelectedSecondaryOwners();
            populateCCOwnerSelects();
        }
        if (modalId === 'companyModal' && !isEdit) {
            document.getElementById('companyModalTitle').textContent = 'Add Company';
            document.getElementById('companyForm').reset();
            document.getElementById('companyId').value = '';
            clearParentCompany();
            document.querySelectorAll('input[name="smallBusinessStatus"], input[name="vehicles"]').forEach(cb => cb.checked = false);
            // Reset core customers
            companySelectedCoreCustomerIds = [];
            renderCompanyCoreCustomerList();
            renderCompanySelectedCoreCustomers();
            // Reset custom vehicles
            companyCustomVehicles = [];
            updateVehiclesTags();
        }
        origOpenModal(modalId, isEdit);
    };
    
    // =============================================
    // COMPANY CONTACT DETAIL PANEL
    // =============================================
    
    async function openCompanyContactPanel(contactId) {
        currentCompanyContactId = contactId;
        
        try {
            const response = await fetch(`${API_URL}?action=getCompanyContactDetail&id=${contactId}`);
            const data = await response.json();
            
            if (data.success && data.contact) {
                currentCompanyContactData = data.contact;
                renderCompanyContactPanel();
                loadCompanyContactNotes(contactId);
                
                document.getElementById('companyContactPanelOverlay').classList.add('open');
                document.getElementById('companyContactPanel').classList.add('open');
            }
        } catch (error) {
            console.error('Error loading company contact:', error);
            showToast('Error loading contact details.', 'error');
        }
    }
    
    function renderCompanyContactPanel() {
        const contact = currentCompanyContactData;
        if (!contact) return;
        
        document.getElementById('ccPanelName').textContent = `${contact.first_name || ''} ${contact.last_name || ''}`;
        document.getElementById('ccPanelTitle').textContent = `${contact.title || ''} - ${contact.companyName || 'Unknown Company'}`;
        
        const agenciesHtml = contact.agencies_supported?.length > 0
            ? contact.agencies_supported.map(a => `<span class="company-contact-agency-tag">${a.agency_name}</span>`).join('')
            : '<span style="color: #999;">None specified</span>';
        
        // Primary Owner
        const primaryOwnerHtml = contact.primaryOwnerDisplayName 
            ? `<span class="owner-badge">👤 ${contact.primaryOwnerDisplayName}</span>` 
            : '—';
        
        // Secondary Owners
        const secondaryOwners = companyContactSecondaryOwners[contact.id] || [];
        const secondaryOwnersHtml = secondaryOwners.length > 0
            ? secondaryOwners.map(o => `<span class="assigned-chip">👤 ${o.display_name || o.username}</span>`).join('')
            : '<span style="color: #999;">None</span>';
        
        document.getElementById('ccInfoSection').innerHTML = `
            <div class="contact-info-row">
                <div class="contact-info-label">Company</div>
                <div class="contact-info-value">${contact.companyName || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Primary Owner</div>
                <div class="contact-info-value">${primaryOwnerHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Secondary Owners</div>
                <div class="contact-info-value"><div class="assigned-list">${secondaryOwnersHtml}</div></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Functional Role</div>
                <div class="contact-info-value">${contact.functional_role || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Capture Role</div>
                <div class="contact-info-value">${contact.capture_role || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Email</div>
                <div class="contact-info-value">${contact.email ? `<a href="mailto:${contact.email}">${contact.email}</a>` : '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Phone</div>
                <div class="contact-info-value">${contact.phone ? `<a href="tel:${contact.phone}">${contact.phone}</a>` : '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Status</div>
                <div class="contact-info-value"><span class="status-badge status-${(contact.status || 'active').toLowerCase()}">${contact.status || 'Active'}</span></div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Agencies Supported</div>
                <div class="contact-info-value"><div class="company-contact-agencies-list">${agenciesHtml}</div></div>
            </div>
            ${contact.notes ? `
            <div class="contact-info-row">
                <div class="contact-info-label">Notes</div>
                <div class="contact-info-value">${contact.notes}</div>
            </div>
            ` : ''}
        `;
    }
    
    function closeCompanyContactPanel() {
        document.getElementById('companyContactPanelOverlay').classList.remove('open');
        document.getElementById('companyContactPanel').classList.remove('open');
        currentCompanyContactId = null;
        currentCompanyContactData = null;
    }
    
    function switchCompanyContactTab(tab) {
        document.querySelectorAll('#companyContactPanel .contact-panel-tab').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');
        
        document.getElementById('ccInfoSection').style.display = tab === 'info' ? 'block' : 'none';
        document.getElementById('ccNotesSection').style.display = tab === 'notes' ? 'block' : 'none';
        document.getElementById('ccLinkedSection').style.display = tab === 'linked' ? 'block' : 'none';
        
        if (tab === 'linked') {
            renderCompanyContactLinkedItems();
        }
    }
    
    // =============================================
    // COMPANY PANEL FUNCTIONS
    // =============================================
    
    async function openCompanyPanel(companyId) {
        currentCompanyPanelId = companyId;
        
        try {
            const response = await fetch(`${API_URL}?action=getCompanyDetail&id=${companyId}`);
            const data = await response.json();
            
            if (data.success && data.company) {
                currentCompanyPanelData = data.company;
                renderCompanyPanel();
                
                document.getElementById('companyPanelOverlay').classList.add('open');
                document.getElementById('companyPanel').classList.add('open');
            }
        } catch (error) {
            console.error('Error loading company:', error);
            showToast('Error loading company details.', 'error');
        }
    }
    
    function renderCompanyPanel() {
        const company = currentCompanyPanelData;
        if (!company) return;
        
        document.getElementById('companyPanelName').textContent = company.company_name || 'Unknown Company';
        document.getElementById('companyPanelType').textContent = company.company_type || 'Company';
        
        // Build badges
        const sbStatuses = company.small_business_statuses || [];
        const sbBadgesHtml = sbStatuses.length > 0 
            ? sbStatuses.map(s => `<span class="company-badge sbs">${s}</span>`).join(' ')
            : '<span style="color: #999;">None</span>';
        
        const vehicles = company.vehicles || [];
        const vehiclesBadgesHtml = vehicles.length > 0
            ? vehicles.map(v => `<span class="company-badge vehicle">${v}</span>`).join(' ')
            : '<span style="color: #999;">None</span>';
        
        const coreCustomers = company.core_customers || [];
        const coreCustomersHtml = coreCustomers.length > 0
            ? coreCustomers.map(c => `<span class="company-contact-agency-tag">${c.agency_name}</span>`).join(' ')
            : '<span style="color: #999;">None specified</span>';
        
        // Strategic Importance badge
        let strategicHtml = '—';
        if (company.strategic_importance === 'Anchor Partner') {
            strategicHtml = '<span class="company-badge strategic-anchor">⭐ Anchor Partner</span>';
        } else if (company.strategic_importance === 'Strategic') {
            strategicHtml = '<span class="company-badge strategic-strategic">Strategic</span>';
        } else if (company.strategic_importance === 'Opportunistic') {
            strategicHtml = '<span class="company-badge strategic-opportunistic">Opportunistic</span>';
        }
        
        // Competitive Posture badge
        let postureHtml = '—';
        if (company.competitive_posture === 'Partner') {
            postureHtml = '<span class="company-badge posture-partner">Partner</span>';
        } else if (company.competitive_posture === 'Competitor') {
            postureHtml = '<span class="company-badge posture-competitor">Competitor</span>';
        } else if (company.competitive_posture === 'Partner-Competitor') {
            postureHtml = '<span class="company-badge posture-both">Partner-Competitor</span>';
        }
        
        document.getElementById('companyInfoSection').innerHTML = `
            <div class="company-panel-section-title">📋 Basic Information</div>
            <div class="contact-info-row">
                <div class="contact-info-label">Company Name</div>
                <div class="contact-info-value">${company.company_name || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Type</div>
                <div class="contact-info-value">${company.company_type || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Parent Company</div>
                <div class="contact-info-value">${company.parentCompanyName || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Website</div>
                <div class="contact-info-value">${company.website ? `<a href="${company.website}" target="_blank">${company.website}</a>` : '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Status</div>
                <div class="contact-info-value"><span class="status-badge status-${(company.status || 'active').toLowerCase()}">${company.status || 'Active'}</span></div>
            </div>
            
            <div class="company-panel-section-title" style="margin-top: 20px;">🎯 Strategic Profile</div>
            <div class="contact-info-row">
                <div class="contact-info-label">Strategic Importance</div>
                <div class="contact-info-value">${strategicHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Competitive Posture</div>
                <div class="contact-info-value">${postureHtml}</div>
            </div>
            
            <div class="company-panel-section-title" style="margin-top: 20px;">🏛️ Federal Business</div>
            <div class="contact-info-row">
                <div class="contact-info-label">Small Business</div>
                <div class="contact-info-value" style="flex-wrap: wrap;">${sbBadgesHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Contract Vehicles</div>
                <div class="contact-info-value" style="flex-wrap: wrap;">${vehiclesBadgesHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Core Customers</div>
                <div class="contact-info-value" style="flex-wrap: wrap;">${coreCustomersHtml}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">Primary NAICS</div>
                <div class="contact-info-value">${company.primary_naics_codes || '—'}</div>
            </div>
            
            <div class="company-panel-section-title" style="margin-top: 20px;">🔑 Identifiers</div>
            <div class="contact-info-row">
                <div class="contact-info-label">UEI</div>
                <div class="contact-info-value">${company.uei || '—'}</div>
            </div>
            <div class="contact-info-row">
                <div class="contact-info-label">CAGE Code</div>
                <div class="contact-info-value">${company.cage_code || '—'}</div>
            </div>
            
            ${company.description ? `
            <div class="company-panel-section-title" style="margin-top: 20px;">📝 Description</div>
            <div style="padding: 10px 0; color: #333; line-height: 1.5;">${company.description}</div>
            ` : ''}
        `;
        
        // Render contacts tab
        renderCompanyPanelContacts();
    }
    
    function renderCompanyPanelContacts() {
        const company = currentCompanyPanelData;
        if (!company) return;
        
        const contacts = company.contacts || [];
        const container = document.getElementById('companyContactsSection');
        
        if (contacts.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <p style="font-size: 1.1rem;">No contacts associated with this company.</p>
                    <button class="btn" style="margin-top: 15px;" onclick="closeCompanyPanel(); openModal('companyContactModal')">+ Add Company Contact</button>
                </div>
            `;
            return;
        }
        
        // Sort contacts by last name (should already be sorted from API, but ensure it)
        contacts.sort((a, b) => (a.last_name || '').localeCompare(b.last_name || ''));
        
        container.innerHTML = `
            <div style="margin-bottom: 15px; color: #666; font-size: 0.9rem;">
                ${contacts.length} contact${contacts.length !== 1 ? 's' : ''} at this company
            </div>
            <div class="company-contacts-list">
                ${contacts.map(contact => `
                    <div class="company-contact-card" onclick="openCompanyContactPanel(${contact.id}); closeCompanyPanel();">
                        <div class="company-contact-card-name">${contact.first_name || ''} ${contact.last_name || ''}</div>
                        <div class="company-contact-card-title">${contact.title || '—'}</div>
                        <div class="company-contact-card-meta">
                            ${contact.functional_role ? `<span class="company-badge sbs">${contact.functional_role}</span>` : ''}
                            ${contact.capture_role ? `<span class="company-badge vehicle">${contact.capture_role}</span>` : ''}
                        </div>
                        ${contact.email ? `<div class="company-contact-card-email">${contact.email}</div>` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    function closeCompanyPanel() {
        document.getElementById('companyPanelOverlay').classList.remove('open');
        document.getElementById('companyPanel').classList.remove('open');
        currentCompanyPanelId = null;
        currentCompanyPanelData = null;
    }
    
    function switchCompanyPanelTab(tab) {
        document.querySelectorAll('#companyPanel .contact-panel-tab').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');
        
        document.getElementById('companyInfoSection').style.display = tab === 'info' ? 'block' : 'none';
        document.getElementById('companyContactsSection').style.display = tab === 'contacts' ? 'block' : 'none';
    }
    
    function renderCompanyContactLinkedItems() {
        const contactId = currentCompanyContactId;
        
        // Render linked opportunities
        const opps = companyContactOpportunities[contactId] || [];
        const oppContainer = document.getElementById('ccLinkedOpportunities');
        if (opps.length === 0) {
            oppContainer.innerHTML = '<div class="no-linked">No linked opportunities</div>';
        } else {
            oppContainer.innerHTML = opps.map(opp => `
                <div class="linked-item opportunity">
                    <div class="linked-item-info">
                        <div class="linked-item-title">${opp.opportunityTitle || 'Untitled'}</div>
                        <div class="linked-item-meta">
                            <span class="linked-item-status ${(opp.opportunityStatus || '').toLowerCase()}">${opp.opportunityStatus || 'N/A'}</span>
                            ${opp.role ? `<span>Role: ${opp.role}</span>` : ''}
                        </div>
                    </div>
                    <div class="linked-item-actions">
                        <button class="linked-unlink-btn" onclick="unlinkContactOpportunity(${contactId}, ${opp.opportunity_id}, 'company')">✕ Unlink</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Render linked proposals
        const props = companyContactProposals[contactId] || [];
        const propContainer = document.getElementById('ccLinkedProposals');
        if (props.length === 0) {
            propContainer.innerHTML = '<div class="no-linked">No linked proposals</div>';
        } else {
            propContainer.innerHTML = props.map(prop => `
                <div class="linked-item proposal">
                    <div class="linked-item-info">
                        <div class="linked-item-title">${prop.proposalTitle || 'Untitled'}</div>
                        <div class="linked-item-meta">
                            <span class="linked-item-status ${(prop.proposalStatus || '').toLowerCase().replace(' ', '-')}">${prop.proposalStatus || 'N/A'}</span>
                            ${prop.role ? `<span>Role: ${prop.role}</span>` : ''}
                        </div>
                    </div>
                    <div class="linked-item-actions">
                        <button class="linked-unlink-btn" onclick="unlinkContactProposal(${contactId}, ${prop.proposal_id}, 'company')">✕ Unlink</button>
                    </div>
                </div>
            `).join('');
        }
    }
    
    function editCompanyContactFromPanel() {
        if (currentCompanyContactId) {
            closeCompanyContactPanel();
            editCompanyContact(currentCompanyContactId);
        }
    }
    
    // =============================================
    // CONTACT-OPPORTUNITY-PROPOSAL LINKING
    // =============================================
    
    function openLinkOpportunityModal(contactType) {
        const contactId = contactType === 'federal' ? currentContactId : currentCompanyContactId;
        document.getElementById('linkOppContactId').value = contactId;
        document.getElementById('linkOppContactType').value = contactType;
        document.getElementById('linkOppRole').value = '';
        
        // Populate opportunities dropdown (exclude already linked)
        const linkedOppIds = contactType === 'federal' 
            ? (contactOpportunities[contactId] || []).map(o => o.opportunity_id)
            : (companyContactOpportunities[contactId] || []).map(o => o.opportunity_id);
        
        const availableOpps = opportunities.filter(o => !linkedOppIds.includes(o.id));
        const select = document.getElementById('linkOppSelect');
        select.innerHTML = '<option value="">Choose an opportunity...</option>' +
            availableOpps.map(o => `<option value="${o.id}">${o.title} (${o.status})</option>`).join('');
        
        openModal('linkOpportunityModal');
    }
    
    function openLinkProposalModal(contactType) {
        const contactId = contactType === 'federal' ? currentContactId : currentCompanyContactId;
        document.getElementById('linkPropContactId').value = contactId;
        document.getElementById('linkPropContactType').value = contactType;
        document.getElementById('linkPropRole').value = '';
        
        // Populate proposals dropdown (exclude already linked)
        const linkedPropIds = contactType === 'federal' 
            ? (contactProposals[contactId] || []).map(p => p.proposal_id)
            : (companyContactProposals[contactId] || []).map(p => p.proposal_id);
        
        const availableProps = proposals.filter(p => !linkedPropIds.includes(p.id));
        const select = document.getElementById('linkPropSelect');
        select.innerHTML = '<option value="">Choose a proposal...</option>' +
            availableProps.map(p => `<option value="${p.id}">${p.title} (${p.status})</option>`).join('');
        
        openModal('linkProposalModal');
    }
    
    async function saveLinkOpportunity() {
        const contactId = parseInt(document.getElementById('linkOppContactId').value);
        const contactType = document.getElementById('linkOppContactType').value;
        const opportunityId = parseInt(document.getElementById('linkOppSelect').value);
        const role = document.getElementById('linkOppRole').value;
        
        if (!opportunityId) {
            showToast('Please select an opportunity.', 'warning');
            return;
        }
        
        const action = contactType === 'federal' ? 'saveContactOpportunity' : 'saveCompanyContactOpportunity';
        const data = contactType === 'federal' 
            ? { contact_id: contactId, opportunity_id: opportunityId, role }
            : { company_contact_id: contactId, opportunity_id: opportunityId, role };
        
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('linkOpportunityModal');
                await fetchAllData();
                // Re-render the linked items
                if (contactType === 'federal') {
                    renderContactLinkedItems();
                } else {
                    renderCompanyContactLinkedItems();
                }
            } else {
                showToast('Error linking opportunity: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error linking opportunity', 'error');
        }
    }
    
    async function saveLinkProposal() {
        const contactId = parseInt(document.getElementById('linkPropContactId').value);
        const contactType = document.getElementById('linkPropContactType').value;
        const proposalId = parseInt(document.getElementById('linkPropSelect').value);
        const role = document.getElementById('linkPropRole').value;
        
        if (!proposalId) {
            showToast('Please select a proposal.', 'warning');
            return;
        }
        
        const action = contactType === 'federal' ? 'saveContactProposal' : 'saveCompanyContactProposal';
        const data = contactType === 'federal' 
            ? { contact_id: contactId, proposal_id: proposalId, role }
            : { company_contact_id: contactId, proposal_id: proposalId, role };
        
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('linkProposalModal');
                await fetchAllData();
                // Re-render the linked items
                if (contactType === 'federal') {
                    renderContactLinkedItems();
                } else {
                    renderCompanyContactLinkedItems();
                }
            } else {
                showToast('Error linking proposal: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error linking proposal', 'error');
        }
    }
    
    async function unlinkContactOpportunity(contactId, opportunityId, contactType) {
        if (!confirm('Remove this opportunity link?')) return;
        
        const action = contactType === 'federal' ? 'deleteContactOpportunity' : 'deleteCompanyContactOpportunity';
        const params = contactType === 'federal' 
            ? `contact_id=${contactId}&opportunity_id=${opportunityId}`
            : `company_contact_id=${contactId}&opportunity_id=${opportunityId}`;
        
        try {
            const response = await fetch(`${API_URL}?action=${action}&${params}`, { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                await fetchAllData();
                if (contactType === 'federal') {
                    renderContactLinkedItems();
                } else {
                    renderCompanyContactLinkedItems();
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    async function unlinkContactProposal(contactId, proposalId, contactType) {
        if (!confirm('Remove this proposal link?')) return;
        
        const action = contactType === 'federal' ? 'deleteContactProposal' : 'deleteCompanyContactProposal';
        const params = contactType === 'federal' 
            ? `contact_id=${contactId}&proposal_id=${proposalId}`
            : `company_contact_id=${contactId}&proposal_id=${proposalId}`;
        
        try {
            const response = await fetch(`${API_URL}?action=${action}&${params}`, { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                await fetchAllData();
                if (contactType === 'federal') {
                    renderContactLinkedItems();
                } else {
                    renderCompanyContactLinkedItems();
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // =============================================
    // ADD CONTACTS FROM OPPORTUNITY/PROPOSAL SIDE
    // =============================================
    
    function openAddContactToOpportunityModal() {
        const oppId = document.getElementById('opportunityId').value;
        if (!oppId) return;
        
        document.getElementById('addContactOppId').value = oppId;
        document.getElementById('addContactOppContactId').value = '';
        document.getElementById('addContactOppContactType').value = '';
        document.getElementById('addContactOppRole').value = '';
        document.getElementById('addContactOppSearch').value = '';
        document.getElementById('selectedContactOpp').style.display = 'none';
        document.getElementById('addContactOppSearch').style.display = 'block';
        populateContactsForOpportunity('');
        openModal('addContactToOpportunityModal');
    }
    
    function openAddContactToProposalModal() {
        const propId = document.getElementById('proposalId').value;
        if (!propId) return;
        
        document.getElementById('addContactPropId').value = propId;
        document.getElementById('addContactPropContactId').value = '';
        document.getElementById('addContactPropContactType').value = '';
        document.getElementById('addContactPropRole').value = '';
        document.getElementById('addContactPropSearch').value = '';
        document.getElementById('selectedContactProp').style.display = 'none';
        document.getElementById('addContactPropSearch').style.display = 'block';
        populateContactsForProposal('');
        openModal('addContactToProposalModal');
    }
    
    // Get combined contacts list for opportunity/proposal (excluding already linked)
    function getAvailableContactsForOpportunity(oppId) {
        const linkedContacts = opportunityContacts[oppId] || [];
        const linkedFederalIds = linkedContacts.filter(c => c.contact_type === 'federal').map(c => c.contact_id);
        const linkedCompanyIds = linkedContacts.filter(c => c.contact_type === 'company').map(c => c.contact_id);
        
        const federalList = contacts
            .filter(c => !linkedFederalIds.includes(c.id))
            .map(c => ({
                id: c.id,
                contactType: 'federal',
                title: `${c.firstName} ${c.lastName}`,
                subtitle: `${c.title || ''} • ${c.agencyName || 'No Agency'}`,
                sortName: `${c.firstName} ${c.lastName}`.toLowerCase()
            }));
        
        const commercialList = companyContacts
            .filter(c => !linkedCompanyIds.includes(c.id))
            .map(c => ({
                id: c.id,
                contactType: 'commercial',
                title: `${c.first_name} ${c.last_name}`,
                subtitle: `${c.title || ''} • ${c.companyName || 'No Company'}`,
                sortName: `${c.first_name} ${c.last_name}`.toLowerCase()
            }));
        
        return [...federalList, ...commercialList].sort((a, b) => a.sortName.localeCompare(b.sortName));
    }
    
    function getAvailableContactsForProposal(propId) {
        const linkedContacts = proposalContacts[propId] || [];
        const linkedFederalIds = linkedContacts.filter(c => c.contact_type === 'federal').map(c => c.contact_id);
        const linkedCompanyIds = linkedContacts.filter(c => c.contact_type === 'company').map(c => c.contact_id);
        
        const federalList = contacts
            .filter(c => !linkedFederalIds.includes(c.id))
            .map(c => ({
                id: c.id,
                contactType: 'federal',
                title: `${c.firstName} ${c.lastName}`,
                subtitle: `${c.title || ''} • ${c.agencyName || 'No Agency'}`,
                sortName: `${c.firstName} ${c.lastName}`.toLowerCase()
            }));
        
        const commercialList = companyContacts
            .filter(c => !linkedCompanyIds.includes(c.id))
            .map(c => ({
                id: c.id,
                contactType: 'commercial',
                title: `${c.first_name} ${c.last_name}`,
                subtitle: `${c.title || ''} • ${c.companyName || 'No Company'}`,
                sortName: `${c.first_name} ${c.last_name}`.toLowerCase()
            }));
        
        return [...federalList, ...commercialList].sort((a, b) => a.sortName.localeCompare(b.sortName));
    }
    
    function populateContactsForOpportunity(searchTerm) {
        const oppId = parseInt(document.getElementById('addContactOppId').value);
        const dropdown = document.getElementById('addContactOppDropdown');
        if (!dropdown) return;
        
        const allContacts = getAvailableContactsForOpportunity(oppId);
        const filtered = allContacts.filter(item => 
            item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.subtitle.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        if (filtered.length === 0) {
            dropdown.innerHTML = '<div class="search-select-no-results">No contacts found</div>';
        } else {
            dropdown.innerHTML = filtered.map(item => {
                const badge = `<span class="contact-type-badge ${item.contactType}">${item.contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>`;
                return `
                <div class="search-select-item" onclick="selectContactForOpportunity(${item.id}, '${item.contactType}', '${item.title.replace(/'/g, "\\'")}', '${item.subtitle.replace(/'/g, "\\'")}')">
                    <div class="item-title">${item.title}${badge}</div>
                    <div class="item-subtitle">${item.subtitle}</div>
                </div>
            `}).join('');
        }
    }
    
    function populateContactsForProposal(searchTerm) {
        const propId = parseInt(document.getElementById('addContactPropId').value);
        const dropdown = document.getElementById('addContactPropDropdown');
        if (!dropdown) return;
        
        const allContacts = getAvailableContactsForProposal(propId);
        const filtered = allContacts.filter(item => 
            item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
            item.subtitle.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        if (filtered.length === 0) {
            dropdown.innerHTML = '<div class="search-select-no-results">No contacts found</div>';
        } else {
            dropdown.innerHTML = filtered.map(item => {
                const badge = `<span class="contact-type-badge ${item.contactType}">${item.contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>`;
                return `
                <div class="search-select-item" onclick="selectContactForProposal(${item.id}, '${item.contactType}', '${item.title.replace(/'/g, "\\'")}', '${item.subtitle.replace(/'/g, "\\'")}')">
                    <div class="item-title">${item.title}${badge}</div>
                    <div class="item-subtitle">${item.subtitle}</div>
                </div>
            `}).join('');
        }
    }
    
    function filterContactsForOpportunity() {
        const searchTerm = document.getElementById('addContactOppSearch').value;
        populateContactsForOpportunity(searchTerm);
    }
    
    function filterContactsForProposal() {
        const searchTerm = document.getElementById('addContactPropSearch').value;
        populateContactsForProposal(searchTerm);
    }
    
    function showContactOppDropdown() {
        const dropdown = document.getElementById('addContactOppDropdown');
        if (dropdown) dropdown.classList.add('show');
    }
    
    function showContactPropDropdown() {
        const dropdown = document.getElementById('addContactPropDropdown');
        if (dropdown) dropdown.classList.add('show');
    }
    
    function selectContactForOpportunity(id, contactType, title, subtitle) {
        document.getElementById('addContactOppContactId').value = id;
        document.getElementById('addContactOppContactType').value = contactType;
        document.getElementById('addContactOppSearch').value = '';
        document.getElementById('addContactOppSearch').style.display = 'none';
        
        const badge = `<span class="contact-type-badge ${contactType}">${contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>`;
        const selectedDisplay = document.getElementById('selectedContactOpp');
        selectedDisplay.innerHTML = `
            <div class="item-info">
                <div class="item-name" style="display: flex; align-items: center; gap: 8px;">${title}${badge}</div>
                <div class="item-detail">${subtitle}</div>
            </div>
            <button type="button" class="remove-btn" onclick="clearContactForOpportunity()">×</button>
        `;
        selectedDisplay.style.display = 'flex';
        
        document.getElementById('addContactOppDropdown').classList.remove('show');
    }
    
    function selectContactForProposal(id, contactType, title, subtitle) {
        document.getElementById('addContactPropContactId').value = id;
        document.getElementById('addContactPropContactType').value = contactType;
        document.getElementById('addContactPropSearch').value = '';
        document.getElementById('addContactPropSearch').style.display = 'none';
        
        const badge = `<span class="contact-type-badge ${contactType}">${contactType === 'commercial' ? 'Commercial' : 'Federal'}</span>`;
        const selectedDisplay = document.getElementById('selectedContactProp');
        selectedDisplay.innerHTML = `
            <div class="item-info">
                <div class="item-name" style="display: flex; align-items: center; gap: 8px;">${title}${badge}</div>
                <div class="item-detail">${subtitle}</div>
            </div>
            <button type="button" class="remove-btn" onclick="clearContactForProposal()">×</button>
        `;
        selectedDisplay.style.display = 'flex';
        
        document.getElementById('addContactPropDropdown').classList.remove('show');
    }
    
    function clearContactForOpportunity() {
        document.getElementById('addContactOppContactId').value = '';
        document.getElementById('addContactOppContactType').value = '';
        document.getElementById('selectedContactOpp').style.display = 'none';
        document.getElementById('addContactOppSearch').value = '';
        document.getElementById('addContactOppSearch').style.display = 'block';
    }
    
    function clearContactForProposal() {
        document.getElementById('addContactPropContactId').value = '';
        document.getElementById('addContactPropContactType').value = '';
        document.getElementById('selectedContactProp').style.display = 'none';
        document.getElementById('addContactPropSearch').value = '';
        document.getElementById('addContactPropSearch').style.display = 'block';
    }
    
    async function saveContactToOpportunity() {
        const oppId = parseInt(document.getElementById('addContactOppId').value);
        const contactType = document.getElementById('addContactOppContactType').value;
        const contactId = parseInt(document.getElementById('addContactOppContactId').value);
        const role = document.getElementById('addContactOppRole').value;
        
        if (!contactId) {
            showToast('Please select a contact.', 'warning');
            return;
        }
        
        // Save current form values before fetchAllData resets them
        const savedAgency = document.getElementById('opportunityAgency').value;
        const savedOwner = document.getElementById('opportunityOwner').value;
        const savedDivision = document.getElementById('opportunityDivision').value;
        
        // Map 'commercial' to 'company' for API compatibility
        const apiContactType = contactType === 'commercial' ? 'company' : 'federal';
        const action = apiContactType === 'federal' ? 'saveContactOpportunity' : 'saveCompanyContactOpportunity';
        const data = apiContactType === 'federal' 
            ? { contact_id: contactId, opportunity_id: oppId, role }
            : { company_contact_id: contactId, opportunity_id: oppId, role };
        
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('addContactToOpportunityModal');
                await fetchAllData();
                
                // Restore the form values that were reset by fetchAllData/populateSelects
                document.getElementById('opportunityAgency').value = savedAgency;
                document.getElementById('opportunityOwner').value = savedOwner;
                // Reload divisions for the agency and restore selection
                await loadDivisionsForAgency('opportunity');
                document.getElementById('opportunityDivision').value = savedDivision;
                
                renderOpportunityLinkedContacts(oppId);
            } else {
                showToast('Error adding contact: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error adding contact', 'error');
        }
    }
    
    async function saveContactToProposal() {
        const propId = parseInt(document.getElementById('addContactPropId').value);
        const contactType = document.getElementById('addContactPropContactType').value;
        const contactId = parseInt(document.getElementById('addContactPropContactId').value);
        const role = document.getElementById('addContactPropRole').value;
        
        if (!contactId) {
            showToast('Please select a contact.', 'warning');
            return;
        }
        
        // Save current form values before fetchAllData resets them
        const savedAgency = document.getElementById('proposalAgency').value;
        const savedOwner = document.getElementById('proposalOwner').value;
        
        // Map 'commercial' to 'company' for API compatibility
        const apiContactType = contactType === 'commercial' ? 'company' : 'federal';
        const action = apiContactType === 'federal' ? 'saveContactProposal' : 'saveCompanyContactProposal';
        const data = apiContactType === 'federal' 
            ? { contact_id: contactId, proposal_id: propId, role }
            : { company_contact_id: contactId, proposal_id: propId, role };
        
        try {
            const response = await fetch(`${API_URL}?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('addContactToProposalModal');
                await fetchAllData();
                
                // Restore the form values that were reset by fetchAllData/populateSelects
                document.getElementById('proposalAgency').value = savedAgency;
                document.getElementById('proposalOwner').value = savedOwner;
                
                renderProposalLinkedContacts(propId);
            } else {
                showToast('Error adding contact: ' + (result.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error adding contact', 'error');
        }
    }
    
    function renderOpportunityLinkedContacts(oppId) {
        const container = document.getElementById('opportunityLinkedContacts');
        if (!container) return;
        
        const linkedContacts = opportunityContacts[oppId] || [];
        
        if (linkedContacts.length === 0) {
            container.innerHTML = '<div class="no-linked">No linked contacts</div>';
            return;
        }
        
        container.innerHTML = linkedContacts.map(c => `
            <div class="linked-item ${c.contact_type === 'federal' ? 'opportunity' : 'proposal'}">
                <div class="linked-item-info">
                    <div class="linked-item-title">${c.contactName || 'Unknown'}</div>
                    <div class="linked-item-meta">
                        <span class="linked-item-status">${c.contact_type === 'federal' ? '👤 Federal' : '🏢 Company'}</span>
                        ${c.contactTitle ? `<span>${c.contactTitle}</span>` : ''}
                        ${c.role ? `<span>Role: ${c.role}</span>` : ''}
                    </div>
                </div>
                <div class="linked-item-actions">
                    <button type="button" class="linked-unlink-btn" onclick="removeContactFromOpportunity(${c.contact_id}, ${oppId}, '${c.contact_type}')">✕ Remove</button>
                </div>
            </div>
        `).join('');
    }
    
    function renderProposalLinkedContacts(propId) {
        const container = document.getElementById('proposalLinkedContacts');
        if (!container) return;
        
        const linkedContacts = proposalContacts[propId] || [];
        
        if (linkedContacts.length === 0) {
            container.innerHTML = '<div class="no-linked">No linked contacts</div>';
            return;
        }
        
        container.innerHTML = linkedContacts.map(c => `
            <div class="linked-item ${c.contact_type === 'federal' ? 'opportunity' : 'proposal'}">
                <div class="linked-item-info">
                    <div class="linked-item-title">${c.contactName || 'Unknown'}</div>
                    <div class="linked-item-meta">
                        <span class="linked-item-status">${c.contact_type === 'federal' ? '👤 Federal' : '🏢 Company'}</span>
                        ${c.contactTitle ? `<span>${c.contactTitle}</span>` : ''}
                        ${c.role ? `<span>Role: ${c.role}</span>` : ''}
                    </div>
                </div>
                <div class="linked-item-actions">
                    <button type="button" class="linked-unlink-btn" onclick="removeContactFromProposal(${c.contact_id}, ${propId}, '${c.contact_type}')">✕ Remove</button>
                </div>
            </div>
        `).join('');
    }
    
    async function removeContactFromOpportunity(contactId, oppId, contactType) {
        if (!confirm('Remove this contact from the opportunity?')) return;
        
        // Save current form values before fetchAllData resets them
        const savedAgency = document.getElementById('opportunityAgency').value;
        const savedOwner = document.getElementById('opportunityOwner').value;
        const savedDivision = document.getElementById('opportunityDivision').value;
        
        const action = contactType === 'federal' ? 'deleteContactOpportunity' : 'deleteCompanyContactOpportunity';
        const params = contactType === 'federal' 
            ? `contact_id=${contactId}&opportunity_id=${oppId}`
            : `company_contact_id=${contactId}&opportunity_id=${oppId}`;
        
        try {
            const response = await fetch(`${API_URL}?action=${action}&${params}`, { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                await fetchAllData();
                
                // Restore the form values that were reset by fetchAllData/populateSelects
                document.getElementById('opportunityAgency').value = savedAgency;
                document.getElementById('opportunityOwner').value = savedOwner;
                // Reload divisions for the agency and restore selection
                await loadDivisionsForAgency('opportunity');
                document.getElementById('opportunityDivision').value = savedDivision;
                
                renderOpportunityLinkedContacts(oppId);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    async function removeContactFromProposal(contactId, propId, contactType) {
        if (!confirm('Remove this contact from the proposal?')) return;
        
        // Save current form values before fetchAllData resets them
        const savedAgency = document.getElementById('proposalAgency').value;
        const savedOwner = document.getElementById('proposalOwner').value;
        
        const action = contactType === 'federal' ? 'deleteContactProposal' : 'deleteCompanyContactProposal';
        const params = contactType === 'federal' 
            ? `contact_id=${contactId}&proposal_id=${propId}`
            : `company_contact_id=${contactId}&proposal_id=${propId}`;
        
        try {
            const response = await fetch(`${API_URL}?action=${action}&${params}`, { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                await fetchAllData();
                
                // Restore the form values that were reset by fetchAllData/populateSelects
                document.getElementById('proposalAgency').value = savedAgency;
                document.getElementById('proposalOwner').value = savedOwner;
                
                renderProposalLinkedContacts(propId);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // =============================================
    // COMPANY CONTACT NOTES
    // =============================================
    
    async function loadCompanyContactNotes(contactId) {
        try {
            const response = await fetch(`${API_URL}?action=getCompanyContactNotes&contact_id=${contactId}`);
            const data = await response.json();
            
            if (data.success) {
                currentCompanyContactNotes = data.notes || [];
                renderCompanyContactNotes();
            }
        } catch (error) {
            console.error('Error loading company contact notes:', error);
        }
    }
    
    function renderCompanyContactNotes() {
        const container = document.getElementById('ccNotesList');
        if (!container) return;
        
        if (currentCompanyContactNotes.length === 0) {
            container.innerHTML = '<div class="no-notes">No notes yet. Click "Add Note" to create one.</div>';
            return;
        }
        
        container.innerHTML = currentCompanyContactNotes.map(note => {
            const date = new Date(note.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
            const canEdit = note.created_by == currentUserId || currentRole === 'admin';
            
            return `
                <div class="note-item">
                    <div class="note-header">
                        <span class="note-type">${note.note_type || 'General'}</span>
                        <span class="note-date">${date}</span>
                    </div>
                    <div class="note-content">${note.note_text || ''}</div>
                    <div class="note-footer">
                        <span class="note-author">By: ${note.createdByDisplayName || note.createdByUsername || 'Unknown'}</span>
                        ${canEdit ? `
                            <div class="note-actions">
                                <button onclick="editCCNote(${note.id})">Edit</button>
                                <button onclick="deleteCCNote(${note.id})">Delete</button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    function openAddCCNoteModal() {
        editingCCNoteId = null;
        document.getElementById('ccNoteModalTitle').textContent = 'Add Note';
        document.getElementById('ccNoteForm').reset();
        document.getElementById('ccNoteId').value = '';
        document.getElementById('ccNoteContactId').value = currentCompanyContactId;
        document.getElementById('ccNoteModal').style.display = 'block';
    }
    
    function editCCNote(noteId) {
        const note = currentCompanyContactNotes.find(n => n.id == noteId);
        if (!note) return;
        
        editingCCNoteId = noteId;
        document.getElementById('ccNoteModalTitle').textContent = 'Edit Note';
        document.getElementById('ccNoteId').value = note.id;
        document.getElementById('ccNoteContactId').value = currentCompanyContactId;
        document.getElementById('ccNoteType').value = note.note_type || 'General';
        document.getElementById('ccNoteText').value = note.note_text || '';
        document.getElementById('ccNoteModal').style.display = 'block';
    }
    
    async function deleteCCNote(noteId) {
        if (!confirm('Are you sure you want to delete this note?')) return;
        
        try {
            const response = await fetch(`${API_URL}?action=deleteCompanyContactNote&id=${noteId}`, { method: 'POST' });
            const data = await response.json();
            
            if (data.success) {
                await loadCompanyContactNotes(currentCompanyContactId);
            } else {
                showToast('Error deleting note: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            showToast('Error deleting note. Please try again.', 'error');
        }
    }
    
    // Company Contact Note form submit
    document.getElementById('ccNoteForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const noteData = {
            id: document.getElementById('ccNoteId').value || null,
            company_contact_id: document.getElementById('ccNoteContactId').value,
            note_type: document.getElementById('ccNoteType').value,
            note_text: document.getElementById('ccNoteText').value
        };
        
        try {
            const response = await fetch(`${API_URL}?action=saveCompanyContactNote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(noteData)
            });
            const data = await response.json();
            
            if (data.success) {
                closeModal('ccNoteModal');
                await loadCompanyContactNotes(currentCompanyContactId);
            } else {
                showToast('Error saving note: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error saving note:', error);
            showToast('Error saving note. Please try again.', 'error');
        }
    });

    // =============================================
    // DOCUMENT MANAGEMENT FUNCTIONS
    // =============================================
    
    // Load documents for an opportunity
    async function loadOpportunityDocuments(opportunityId) {
        console.log('loadOpportunityDocuments called with ID:', opportunityId);
        try {
            const url = `api.php?action=getOpportunityDocuments&opportunity_id=${opportunityId}`;
            console.log('Fetching from:', url);
            const response = await fetch(url);
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                console.log('Rendering documents:', data.documents);
                renderOpportunityDocuments(data.documents);
            } else {
                console.error('Error loading documents:', data.error);
            }
        } catch (error) {
            console.error('Error loading documents:', error);
        }
    }
    
    // Load documents for a proposal
    async function loadProposalDocuments(proposalId) {
        try {
            const response = await fetch(`api.php?action=getProposalDocuments&proposal_id=${proposalId}`);
            const data = await response.json();
            
            if (data.success) {
                renderProposalDocuments(data.documents);
            } else {
                console.error('Error loading documents:', data.error);
            }
        } catch (error) {
            console.error('Error loading documents:', error);
        }
    }
    
    // Render opportunity documents list
    function renderOpportunityDocuments(documents) {
        console.log('renderOpportunityDocuments called with:', documents);
        const container = document.getElementById('opportunityDocumentsList');
        console.log('Container element:', container);
        
        if (!documents || documents.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; font-style: italic; padding: 10px;">No documents attached yet.</p>';
            return;
        }
        
        const html = documents.map(doc => `
            <div class="document-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef;">
                <div style="flex: 1;">
                    <div style="font-weight: 500;">${getFileIcon(doc.file_name)} ${escapeHtml(doc.file_name)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d;">
                        Uploaded by ${escapeHtml(doc.uploadedByDisplayName || doc.uploadedByUsername)} on ${formatDate(doc.uploaded_at)}
                        ${doc.file_size ? ' • ' + formatFileSize(doc.file_size) : ''}
                    </div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="action-btn view" onclick="downloadDocument('opportunity', ${doc.id})" title="Download">⬇</button>
                    <button class="action-btn delete" onclick="deleteOpportunityDocument(${doc.id})" title="Delete">✕</button>
                </div>
            </div>
        `).join('');
        console.log('Setting innerHTML to:', html);
        container.innerHTML = html;
    }
    
    // Render proposal documents list
    function renderProposalDocuments(documents) {
        const container = document.getElementById('proposalDocumentsList');
        
        if (documents.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; font-style: italic; padding: 10px;">No documents attached yet.</p>';
            return;
        }
        
        container.innerHTML = documents.map(doc => `
            <div class="document-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef;">
                <div style="flex: 1;">
                    <div style="font-weight: 500;">${getFileIcon(doc.file_name)} ${escapeHtml(doc.file_name)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d;">
                        Uploaded by ${escapeHtml(doc.uploadedByDisplayName || doc.uploadedByUsername)} on ${formatDate(doc.uploaded_at)}
                        ${doc.file_size ? ' • ' + formatFileSize(doc.file_size) : ''}
                    </div>
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="action-btn view" onclick="downloadDocument('proposal', ${doc.id})" title="Download">⬇</button>
                    <button class="action-btn delete" onclick="deleteProposalDocument(${doc.id})" title="Delete">✕</button>
                </div>
            </div>
        `).join('');
    }
    
    // Upload document to opportunity
    async function uploadOpportunityDocument() {
        const fileInput = document.getElementById('opportunityDocumentUpload');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        const opportunityId = document.getElementById('opportunityId').value;
        if (!opportunityId) {
            showToast('Please save the opportunity first before uploading documents.', 'warning');
            fileInput.value = '';
            return;
        }
        
        const formData = new FormData();
        formData.append('opportunity_id', opportunityId);
        formData.append('document', file);
        
        try {
            const response = await fetch('api.php?action=uploadOpportunityDocument', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadOpportunityDocuments(opportunityId);
            } else {
                showToast('Error uploading document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            showToast('Error uploading document. Please try again.', 'error');
        }
        
        fileInput.value = '';
    }
    
    // Upload document to proposal
    async function uploadProposalDocument() {
        const fileInput = document.getElementById('proposalDocumentUpload');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        const proposalId = document.getElementById('proposalId').value;
        if (!proposalId) {
            showToast('Please save the proposal first before uploading documents.', 'warning');
            fileInput.value = '';
            return;
        }
        
        const formData = new FormData();
        formData.append('proposal_id', proposalId);
        formData.append('document', file);
        
        try {
            const response = await fetch('api.php?action=uploadProposalDocument', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadProposalDocuments(proposalId);
            } else {
                showToast('Error uploading document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            showToast('Error uploading document. Please try again.', 'error');
        }
        
        fileInput.value = '';
    }
    
    // Delete opportunity document
    async function deleteOpportunityDocument(documentId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            const response = await fetch('api.php?action=deleteOpportunityDocument', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: documentId })
            });
            const data = await response.json();
            
            if (data.success) {
                const opportunityId = document.getElementById('opportunityId').value;
                loadOpportunityDocuments(opportunityId);
            } else {
                showToast('Error deleting document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showToast('Error deleting document. Please try again.', 'error');
        }
    }
    
    // Delete proposal document
    async function deleteProposalDocument(documentId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            const response = await fetch('api.php?action=deleteProposalDocument', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: documentId })
            });
            const data = await response.json();
            
            if (data.success) {
                const proposalId = document.getElementById('proposalId').value;
                loadProposalDocuments(proposalId);
            } else {
                showToast('Error deleting document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showToast('Error deleting document. Please try again.', 'error');
        }
    }
    
    // =============================================
    // OPPORTUNITY PANEL DOCUMENT FUNCTIONS
    // =============================================
    
    // Load documents for opportunity panel
    async function loadOppPanelDocuments(opportunityId) {
        const container = document.getElementById('oppPanelDocumentsList');
        if (!container) return;
        
        try {
            const response = await fetch(`api.php?action=getOpportunityDocuments&opportunity_id=${opportunityId}`);
            const data = await response.json();
            
            if (data.success) {
                renderOppPanelDocuments(data.documents, opportunityId);
            } else {
                container.innerHTML = '<p style="color: #dc3545; padding: 10px;">Error loading documents.</p>';
            }
        } catch (error) {
            console.error('Error loading panel documents:', error);
            container.innerHTML = '<p style="color: #dc3545; padding: 10px;">Error loading documents.</p>';
        }
    }
    
    // Render documents in opportunity panel
    function renderOppPanelDocuments(documents, opportunityId) {
        const container = document.getElementById('oppPanelDocumentsList');
        if (!container) return;
        
        if (!documents || documents.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; font-style: italic; padding: 10px;">No documents attached yet.</p>';
            return;
        }
        
        container.innerHTML = documents.map(doc => `
            <div class="document-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${getFileIcon(doc.file_name)} ${escapeHtml(doc.file_name)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d;">
                        Uploaded by ${escapeHtml(doc.uploadedByDisplayName || doc.uploadedByUsername)} on ${formatDate(doc.uploaded_at)}
                        ${doc.file_size ? ' • ' + formatFileSize(doc.file_size) : ''}
                    </div>
                </div>
                <div style="display: flex; gap: 5px; flex-shrink: 0; margin-left: 10px;">
                    <button class="action-btn view" onclick="downloadDocument('opportunity', ${doc.id})" title="Download" style="padding: 5px 8px;">⬇</button>
                    <button class="action-btn delete" onclick="deleteOppPanelDocument(${doc.id}, ${opportunityId})" title="Delete" style="padding: 5px 8px;">✕</button>
                </div>
            </div>
        `).join('');
    }
    
    // Upload document from opportunity panel
    async function uploadOppPanelDocument() {
        const fileInput = document.getElementById('oppPanelDocumentUpload');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        if (!currentOpportunityId) {
            showToast('No opportunity selected.', 'warning');
            fileInput.value = '';
            return;
        }
        
        const formData = new FormData();
        formData.append('opportunity_id', currentOpportunityId);
        formData.append('document', file);
        
        try {
            const response = await fetch('api.php?action=uploadOpportunityDocument', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadOppPanelDocuments(currentOpportunityId);
            } else {
                showToast('Error uploading document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading document:', error);
            showToast('Error uploading document. Please try again.', 'error');
        }
        
        fileInput.value = '';
    }
    
    // Delete document from opportunity panel
    async function deleteOppPanelDocument(documentId, opportunityId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            const response = await fetch('api.php?action=deleteOpportunityDocument', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: documentId })
            });
            const data = await response.json();
            
            if (data.success) {
                loadOppPanelDocuments(opportunityId);
            } else {
                showToast('Error deleting document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting document:', error);
            showToast('Error deleting document. Please try again.', 'error');
        }
    }
    
    // =============================================
    // PROPOSAL PANEL DOCUMENT FUNCTIONS
    // =============================================
    
    // Load documents for proposal panel
    async function loadPropPanelDocuments(proposalId) {
        const container = document.getElementById('propPanelDocumentsList');
        if (!container) return;
        
        try {
            const response = await fetch(`api.php?action=getProposalDocuments&proposal_id=${proposalId}`);
            const data = await response.json();
            
            if (data.success) {
                renderPropPanelDocuments(data.documents, proposalId);
            } else {
                container.innerHTML = '<p style="color: #dc3545; padding: 10px;">Error loading documents.</p>';
            }
        } catch (error) {
            console.error('Error loading proposal panel documents:', error);
            container.innerHTML = '<p style="color: #dc3545; padding: 10px;">Error loading documents.</p>';
        }
    }
    
    // Render documents in proposal panel
    function renderPropPanelDocuments(documents, proposalId) {
        const container = document.getElementById('propPanelDocumentsList');
        if (!container) return;
        
        if (!documents || documents.length === 0) {
            container.innerHTML = '<p style="color: #6c757d; font-style: italic; padding: 10px;">No documents attached yet.</p>';
            return;
        }
        
        container.innerHTML = documents.map(doc => `
            <div class="document-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${getFileIcon(doc.file_name)} ${escapeHtml(doc.file_name)}</div>
                    <div style="font-size: 0.8rem; color: #6c757d;">
                        Uploaded by ${escapeHtml(doc.uploadedByDisplayName || doc.uploadedByUsername)} on ${formatDate(doc.uploaded_at)}
                        ${doc.file_size ? ' • ' + formatFileSize(doc.file_size) : ''}
                    </div>
                </div>
                <div style="display: flex; gap: 5px; flex-shrink: 0; margin-left: 10px;">
                    <button class="action-btn view" onclick="downloadDocument('proposal', ${doc.id})" title="Download" style="padding: 5px 8px;">⬇</button>
                    <button class="action-btn delete" onclick="deletePropPanelDocument(${doc.id}, ${proposalId})" title="Delete" style="padding: 5px 8px;">✕</button>
                </div>
            </div>
        `).join('');
    }
    
    // Upload document from proposal panel
    async function uploadPropPanelDocument() {
        const fileInput = document.getElementById('propPanelDocumentUpload');
        const file = fileInput.files[0];
        
        if (!file) return;
        
        if (!currentProposalId) {
            showToast('No proposal selected.', 'warning');
            fileInput.value = '';
            return;
        }
        
        const formData = new FormData();
        formData.append('proposal_id', currentProposalId);
        formData.append('document', file);
        
        try {
            const response = await fetch('api.php?action=uploadProposalDocument', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadPropPanelDocuments(currentProposalId);
            } else {
                showToast('Error uploading document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error uploading proposal document:', error);
            showToast('Error uploading document. Please try again.', 'error');
        }
        
        fileInput.value = '';
    }
    
    // Delete document from proposal panel
    async function deletePropPanelDocument(documentId, proposalId) {
        if (!confirm('Are you sure you want to delete this document?')) return;
        
        try {
            const response = await fetch('api.php?action=deleteProposalDocument', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: documentId })
            });
            const data = await response.json();
            
            if (data.success) {
                loadPropPanelDocuments(proposalId);
            } else {
                showToast('Error deleting document: ' + (data.error || 'Unknown error', 'error'));
            }
        } catch (error) {
            console.error('Error deleting proposal document:', error);
            showToast('Error deleting document. Please try again.', 'error');
        }
    }
    
    // Download document
    function downloadDocument(type, documentId) {
        window.open(`api.php?action=downloadDocument&type=${type}&id=${documentId}`, '_blank');
    }
    
    // Helper function to get file icon based on extension
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': '📄',
            'doc': '📝', 'docx': '📝',
            'xls': '📊', 'xlsx': '📊',
            'ppt': '📽️', 'pptx': '📽️',
            'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️',
            'txt': '📃',
            'csv': '📋',
            'zip': '📦'
        };
        return icons[ext] || '📎';
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (!bytes) return '';
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < units.length - 1) {
            bytes /= 1024;
            i++;
        }
        return bytes.toFixed(1) + ' ' + units[i];
    }
    
    // Helper function to format date for display
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Helper function to escape HTML
    </script>
    
    <!-- Opportunity Workspace (Full Page - Shipley based) -->
    <div id="oppWorkspaceOverlay" class="opp-workspace-overlay">
        <div class="opp-workspace">
            <!-- Header -->
            <div class="opp-workspace-header">
                <div class="opp-workspace-header-left">
                    <button class="opp-workspace-back" onclick="closeOpportunityWorkspace()">←</button>
                    <div class="opp-workspace-title">
                        <h1 id="oppWorkspaceTitle">Opportunity Name</h1>
                        <p id="oppWorkspaceSubtitle">Agency - Value</p>
                    </div>
                </div>
                <div class="opp-workspace-header-right">
                    <div class="opp-workspace-progress">
                        <span>Progress:</span>
                        <div class="opp-workspace-progress-bar">
                            <div class="opp-workspace-progress-fill" id="oppWorkspaceProgressFill" style="width: 0%"></div>
                        </div>
                        <span id="oppWorkspaceProgressText">0%</span>
                    </div>
                    <span id="oppWorkspacePhaseStatus" class="opp-workspace-status status-qualification">Qualification</span>
                </div>
            </div>
            
            <!-- Phase Tabs -->
            <div class="opp-workspace-phases">
                <div class="opp-phase-tab active" data-phase="qualification" onclick="switchWorkspacePhase('qualification')">
                    <div class="phase-icon">📋</div>
                    <div class="phase-name">Qualification</div>
                    <div class="phase-status" id="phaseStatusQualification">In Progress</div>
                </div>
                <div class="opp-phase-tab locked" data-phase="capture" onclick="switchWorkspacePhase('capture')">
                    <div class="phase-icon">🎯</div>
                    <div class="phase-name">Capture</div>
                    <div class="phase-status" id="phaseStatusCapture">🔒 Locked</div>
                </div>
                <div class="opp-phase-tab locked" data-phase="bid_decision" onclick="switchWorkspacePhase('bid_decision')">
                    <div class="phase-icon">⚖️</div>
                    <div class="phase-name">Bid Decision</div>
                    <div class="phase-status" id="phaseStatusBidDecision">🔒 Locked</div>
                </div>
            </div>
            
            <!-- Phase Content -->
            <div class="opp-workspace-content">
                <!-- QUALIFICATION PHASE -->
                <div class="opp-phase-content active" id="phaseQualification">
                    <!-- Opportunity Profile Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📄</span> Opportunity Profile</h3>
                        </div>
                        <div class="opp-form-grid">
                            <div class="opp-form-group">
                                <label>Solicitation Number</label>
                                <input type="text" id="qualSolicitationNumber" placeholder="e.g., W911QY-24-R-0001">
                            </div>
                            <div class="opp-form-group">
                                <label>NAICS Code *</label>
                                <input type="text" id="qualNaicsCode" placeholder="e.g., 541512">
                            </div>
                            <div class="opp-form-group">
                                <label>Set-Aside Type *</label>
                                <select id="qualSetAsideType">
                                    <option value="full_open">Full & Open</option>
                                    <option value="small_business">Small Business</option>
                                    <option value="8a">8(a)</option>
                                    <option value="sdvosb">SDVOSB</option>
                                    <option value="hubzone">HUBZone</option>
                                    <option value="wosb">WOSB</option>
                                    <option value="edwosb">EDWOSB</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="opp-form-group">
                                <label>Contract Type *</label>
                                <select id="qualContractType">
                                    <option value="ffp">Firm Fixed Price (FFP)</option>
                                    <option value="tm">Time & Materials (T&M)</option>
                                    <option value="cost_plus">Cost-Plus</option>
                                    <option value="idiq">IDIQ</option>
                                    <option value="bpa">BPA</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="opp-form-group">
                                <label>Contract Vehicle</label>
                                <input type="text" id="qualContractVehicle" placeholder="e.g., GSA Schedule, SEWP V">
                            </div>
                            <div class="opp-form-group">
                                <label>Period of Performance</label>
                                <input type="text" id="qualPeriodOfPerformance" placeholder="e.g., 1 Base + 4 Option Years">
                            </div>
                            <div class="opp-form-group">
                                <label>Expected RFP Date</label>
                                <input type="date" id="qualExpectedRfpDate">
                            </div>
                            <div class="opp-form-group">
                                <label>Expected Award Date</label>
                                <input type="date" id="qualExpectedAwardDate">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Qualification Scorecard Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📊</span> Qualification Scorecard</h3>
                            <span style="color: #666; font-size: 0.9rem;">Score each criterion 0-10</span>
                        </div>
                        <div class="opp-scorecard">
                            <div class="opp-scorecard-row header">
                                <div>Criterion</div>
                                <div>Weight</div>
                                <div>Score</div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we know the customer?</div>
                                <div class="opp-scorecard-weight">15%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreKnowCustomer" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Have we worked with them before?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreWorkedBefore" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we have access to decision makers?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreDecisionMakerAccess" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Is this a funded requirement?</div>
                                <div class="opp-scorecard-weight">15%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreFunded" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we understand the scope?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreUnderstandScope" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Is the timeline realistic?</div>
                                <div class="opp-scorecard-weight">5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreRealisticTimeline" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we know the incumbent?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreKnowIncumbent" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Can we beat the competition?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreCanBeatCompetition" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we have the technical capability?</div>
                                <div class="opp-scorecard-weight">10%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scoreTechnicalCapability" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Do we have past performance?</div>
                                <div class="opp-scorecard-weight">5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="scorePastPerformance" min="0" max="10" value="0" onchange="calculateQualificationScore()"></div>
                            </div>
                            <div class="opp-scorecard-total">
                                <div class="opp-scorecard-total-label">Qualification Score</div>
                                <div class="opp-scorecard-total-value score-low" id="qualificationScoreDisplay">0%</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Intelligence Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">🔍</span> Customer Intelligence</h3>
                        </div>
                        <div class="opp-form-grid">
                            <div class="opp-form-group">
                                <label>Customer Pain Points *</label>
                                <textarea id="qualCustomerPainPoints" placeholder="What challenges is the customer facing?"></textarea>
                            </div>
                            <div class="opp-form-group">
                                <label>Hot Buttons</label>
                                <textarea id="qualHotButtons" placeholder="What issues are most important to the customer?"></textarea>
                            </div>
                            <div class="opp-form-group">
                                <label>Evaluation Priorities</label>
                                <textarea id="qualEvaluationPriorities" placeholder="How will proposals be evaluated?"></textarea>
                            </div>
                            <div class="opp-form-group">
                                <label>Incumbent Performance Issues</label>
                                <textarea id="qualIncumbentIssues" placeholder="Any known issues with current contractor?"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Key Contacts Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">👥</span> Key Decision Makers & Influencers</h3>
                            <button class="opp-table-btn add" onclick="showAddQualContactModal()">+ Add Contact</button>
                        </div>
                        <table class="opp-editable-table" id="qualContactsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Role</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="qualContactsTableBody">
                                <tr><td colspan="5" style="text-align: center; color: #999;">No contacts added yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Qualification Phase Tasks -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📋</span> Phase Tasks</h3>
                            <button class="opp-table-btn add" onclick="openTaskModalForOpportunity(currentWorkspaceOppId, 'qualification')">+ Add Task</button>
                        </div>
                        <table class="opp-editable-table" id="qualTasksTable">
                            <thead><tr><th>Task</th><th>Assignee</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="qualTasksTableBody">
                                <tr><td colspan="6" style="text-align:center;color:#999;">No tasks yet</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Qualification Gate Decision -->
                    <div class="opp-gate-decision">
                        <h4>📍 Qualification Gate Decision</h4>
                        <p style="color: #666; margin-bottom: 15px;">Score must be ≥70% to proceed to Capture phase.</p>
                        <div class="opp-form-group">
                            <label>Decision Notes</label>
                            <textarea id="qualDecisionNotes" placeholder="Justification for decision..."></textarea>
                        </div>
                        <div class="opp-gate-buttons">
                            <div class="opp-gate-btn pursue" data-decision="pursue" onclick="selectQualificationDecision('pursue')">
                                <div class="gate-icon">✅</div>
                                <div class="gate-label">Pursue</div>
                            </div>
                            <div class="opp-gate-btn monitor" data-decision="monitor" onclick="selectQualificationDecision('monitor')">
                                <div class="gate-icon">👁️</div>
                                <div class="gate-label">Monitor</div>
                            </div>
                            <div class="opp-gate-btn no-bid" data-decision="no_bid" onclick="selectQualificationDecision('no_bid')">
                                <div class="gate-icon">❌</div>
                                <div class="gate-label">No Bid</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="opp-workspace-actions">
                        <button class="opp-btn opp-btn-secondary" onclick="closeOpportunityWorkspace()">Cancel</button>
                        <button class="opp-btn opp-btn-primary" onclick="saveQualificationData()">💾 Save Qualification</button>
                    </div>
                </div>
                
                <!-- CAPTURE PHASE -->
                <div class="opp-phase-content" id="phaseCapture">
                    <!-- Win Strategy Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">🏆</span> Win Strategy</h3>
                        </div>
                        
                        <div class="opp-win-theme">
                            <div class="opp-win-theme-header">
                                <div class="opp-win-theme-number">1</div>
                                <input type="text" id="capWinTheme1Title" placeholder="Win Theme Title *">
                            </div>
                            <textarea id="capWinTheme1Message" placeholder="Supporting message for this win theme... *"></textarea>
                        </div>
                        
                        <div class="opp-win-theme">
                            <div class="opp-win-theme-header">
                                <div class="opp-win-theme-number">2</div>
                                <input type="text" id="capWinTheme2Title" placeholder="Win Theme Title">
                            </div>
                            <textarea id="capWinTheme2Message" placeholder="Supporting message for this win theme..."></textarea>
                        </div>
                        
                        <div class="opp-win-theme">
                            <div class="opp-win-theme-header">
                                <div class="opp-win-theme-number">3</div>
                                <input type="text" id="capWinTheme3Title" placeholder="Win Theme Title">
                            </div>
                            <textarea id="capWinTheme3Message" placeholder="Supporting message for this win theme..."></textarea>
                        </div>
                        
                        <div class="opp-form-grid" style="margin-top: 20px;">
                            <div class="opp-form-group">
                                <label>Key Discriminators *</label>
                                <textarea id="capDiscriminators" placeholder="What makes us uniquely qualified?"></textarea>
                            </div>
                            <div class="opp-form-group">
                                <label>Ghosting Strategy</label>
                                <textarea id="capGhostingStrategy" placeholder="Competitor weaknesses to highlight..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Competitive Analysis Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">⚔️</span> Competitive Analysis</h3>
                            <button class="opp-table-btn add" onclick="addCompetitorRow()">+ Add Competitor</button>
                        </div>
                        <table class="opp-editable-table" id="competitorsTable">
                            <thead>
                                <tr>
                                    <th>Competitor</th>
                                    <th>Incumbent?</th>
                                    <th>Strengths</th>
                                    <th>Weaknesses</th>
                                    <th>Pwin %</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="competitorsTableBody">
                                <tr><td colspan="6" style="text-align: center; color: #999;">No competitors added yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Solution Development Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">💡</span> Solution Development</h3>
                        </div>
                        <div class="opp-form-grid">
                            <div class="opp-form-group full-width">
                                <label>Technical Approach Summary *</label>
                                <textarea id="capTechnicalApproach" placeholder="High-level technical solution..."></textarea>
                            </div>
                            <div class="opp-form-group full-width">
                                <label>Management Approach Summary *</label>
                                <textarea id="capManagementApproach" placeholder="How we will manage the contract..."></textarea>
                            </div>
                            <div class="opp-form-group full-width">
                                <label>Key Personnel Requirements</label>
                                <textarea id="capKeyPersonnel" placeholder="Required roles and qualifications..."></textarea>
                            </div>
                            <div class="opp-form-group">
                                <label>Teaming Strategy *</label>
                                <select id="capTeamingStrategy">
                                    <option value="prime">Prime Contractor</option>
                                    <option value="sub">Subcontractor</option>
                                    <option value="jv">Joint Venture</option>
                                    <option value="solo">Solo (No Teaming)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Teaming Partners Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">🤝</span> Teaming Partners</h3>
                            <button class="opp-table-btn add" onclick="addTeamingPartnerRow()">+ Add Partner</button>
                        </div>
                        <table class="opp-editable-table" id="teamingPartnersTable">
                            <thead>
                                <tr>
                                    <th>Partner</th>
                                    <th>Role</th>
                                    <th>Capability</th>
                                    <th>Status</th>
                                    <th>Teaming Agmt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="teamingPartnersTableBody">
                                <tr><td colspan="6" style="text-align: center; color: #999;">No teaming partners added yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Price Strategy Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">💰</span> Price Strategy</h3>
                        </div>
                        <div class="opp-form-grid">
                            <div class="opp-form-group">
                                <label>Price-to-Win Estimate *</label>
                                <input type="number" id="capPriceToWin" placeholder="$0.00" step="0.01">
                            </div>
                            <div class="opp-form-group">
                                <label>Pricing Strategy *</label>
                                <select id="capPricingStrategy">
                                    <option value="aggressive">Aggressive (Low Price)</option>
                                    <option value="competitive" selected>Competitive</option>
                                    <option value="premium">Premium (Best Value)</option>
                                </select>
                            </div>
                            <div class="opp-form-group">
                                <label>Target Margin %</label>
                                <input type="number" id="capMarginTarget" placeholder="15" min="0" max="100" step="0.1">
                            </div>
                            <div class="opp-form-group full-width">
                                <label>Key Cost Drivers</label>
                                <textarea id="capCostDrivers" placeholder="Major cost factors and assumptions..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Capture Milestones Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📌</span> Capture Milestones</h3>
                        </div>
                        <div class="opp-milestones">
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestoneDraftRfpReview">
                                <span class="opp-milestone-label">Draft RFP Review</span>
                            </label>
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestoneIndustryDay">
                                <span class="opp-milestone-label">Industry Day Attendance</span>
                            </label>
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestoneQuestionsSubmitted">
                                <span class="opp-milestone-label">Questions Submitted</span>
                            </label>
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestonePinkTeam">
                                <span class="opp-milestone-label">Pink Team Review</span>
                            </label>
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestoneTeamingSigned">
                                <span class="opp-milestone-label">Teaming Agreements Signed</span>
                            </label>
                            <label class="opp-milestone">
                                <input type="checkbox" id="milestonePricingApproved">
                                <span class="opp-milestone-label">Final Pricing Approved</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Capture Phase Tasks -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📋</span> Phase Tasks</h3>
                            <button class="opp-table-btn add" onclick="openTaskModalForOpportunity(currentWorkspaceOppId, 'capture')">+ Add Task</button>
                        </div>
                        <table class="opp-editable-table" id="capTasksTable">
                            <thead><tr><th>Task</th><th>Assignee</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="capTasksTableBody">
                                <tr><td colspan="6" style="text-align:center;color:#999;">No tasks yet</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Capture Gate Decision -->
                    <div class="opp-gate-decision">
                        <h4>📍 Capture Gate Decision</h4>
                        <p style="color: #666; margin-bottom: 15px;">Complete win strategy and solution development to proceed to Bid Decision.</p>
                        <div class="opp-form-group">
                            <label>Decision Notes</label>
                            <textarea id="capDecisionNotes" placeholder="Notes on capture readiness..."></textarea>
                        </div>
                        <div class="opp-gate-buttons">
                            <div class="opp-gate-btn pursue" data-decision="yes" onclick="selectCaptureDecision('yes')">
                                <div class="gate-icon">✅</div>
                                <div class="gate-label">Proceed to Bid</div>
                            </div>
                            <div class="opp-gate-btn no-bid" data-decision="no" onclick="selectCaptureDecision('no')">
                                <div class="gate-icon">⏸️</div>
                                <div class="gate-label">Not Ready</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="opp-workspace-actions">
                        <button class="opp-btn opp-btn-secondary" onclick="switchWorkspacePhase('qualification')">← Back</button>
                        <button class="opp-btn opp-btn-primary" onclick="saveCaptureData()">💾 Save Capture</button>
                    </div>
                </div>
                
                <!-- BID DECISION PHASE -->
                <div class="opp-phase-content" id="phaseBidDecision">
                    <!-- Bid Decision Matrix Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📊</span> Bid Decision Matrix</h3>
                            <span style="color: #666; font-size: 0.9rem;">Score each factor 1-5</span>
                        </div>
                        <div class="opp-scorecard">
                            <div class="opp-scorecard-row header">
                                <div>Factor</div>
                                <div>Weight</div>
                                <div>Score</div>
                            </div>
                            <!-- Strategic Fit -->
                            <div class="opp-scorecard-row" style="background: #e3f2fd;">
                                <div class="opp-scorecard-criterion" style="font-weight: 600;">Strategic Fit (15%)</div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Aligns with company strategy</div>
                                <div class="opp-scorecard-weight">7.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreStrategicAlignment" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Opens new market/customer</div>
                                <div class="opp-scorecard-weight">7.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreNewMarket" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <!-- Win Probability -->
                            <div class="opp-scorecard-row" style="background: #e8f5e9;">
                                <div class="opp-scorecard-criterion" style="font-weight: 600;">Win Probability (25%)</div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Competitive position</div>
                                <div class="opp-scorecard-weight">8.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreCompetitivePosition" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Customer relationship</div>
                                <div class="opp-scorecard-weight">8.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreCustomerRelationship" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Solution readiness</div>
                                <div class="opp-scorecard-weight">8%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreSolutionReadiness" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <!-- Resource Availability -->
                            <div class="opp-scorecard-row" style="background: #fff3e0;">
                                <div class="opp-scorecard-criterion" style="font-weight: 600;">Resource Availability (20%)</div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Proposal team available</div>
                                <div class="opp-scorecard-weight">7%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreProposalTeam" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Key personnel identified</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreKeyPersonnel" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">SMEs available</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreSmesAvailable" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <!-- Risk Assessment -->
                            <div class="opp-scorecard-row" style="background: #fce4ec;">
                                <div class="opp-scorecard-criterion" style="font-weight: 600;">Risk Assessment (20%)</div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Technical risk (5=Low, 1=High)</div>
                                <div class="opp-scorecard-weight">7%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreTechnicalRisk" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Cost/Schedule risk (5=Low, 1=High)</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreCostScheduleRisk" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Contract terms acceptable</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreContractTerms" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <!-- Financial -->
                            <div class="opp-scorecard-row" style="background: #f3e5f5;">
                                <div class="opp-scorecard-criterion" style="font-weight: 600;">Financial (20%)</div>
                                <div></div>
                                <div></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Acceptable margin achievable</div>
                                <div class="opp-scorecard-weight">7%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreAcceptableMargin" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">B&P budget available</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreBpBudget" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-row">
                                <div class="opp-scorecard-criterion">Revenue potential</div>
                                <div class="opp-scorecard-weight">6.5%</div>
                                <div class="opp-scorecard-score"><input type="number" id="bidScoreRevenuePotential" min="1" max="5" value="1" onchange="calculateBidDecisionScore()"></div>
                            </div>
                            <div class="opp-scorecard-total">
                                <div class="opp-scorecard-total-label">Bid Decision Score</div>
                                <div class="opp-scorecard-total-value score-low" id="bidDecisionScoreDisplay">20%</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resource Requirements Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">👥</span> Resource Requirements</h3>
                        </div>
                        <div class="opp-form-grid">
                            <div class="opp-form-group">
                                <label>Proposal Manager</label>
                                <select id="bidProposalManager">
                                    <option value="">Select...</option>
                                </select>
                            </div>
                            <div class="opp-form-group">
                                <label>Technical Writers Needed</label>
                                <input type="number" id="bidTechWritersNeeded" min="0" value="0">
                            </div>
                            <div class="opp-form-group">
                                <label>Technical Writers Available</label>
                                <input type="number" id="bidTechWritersAvailable" min="0" value="0">
                            </div>
                            <div class="opp-form-group">
                                <label>SMEs Needed</label>
                                <input type="number" id="bidSmesNeeded" min="0" value="0">
                            </div>
                            <div class="opp-form-group">
                                <label>SMEs Available</label>
                                <input type="number" id="bidSmesAvailable" min="0" value="0">
                            </div>
                            <div class="opp-form-group">
                                <label>B&P Budget Needed ($)</label>
                                <input type="number" id="bidBpBudgetNeeded" min="0" step="0.01" value="0">
                            </div>
                            <div class="opp-form-group">
                                <label>B&P Budget Available ($)</label>
                                <input type="number" id="bidBpBudgetAvailable" min="0" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Risk Register Section -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">⚠️</span> Risk Register</h3>
                            <button class="opp-table-btn add" onclick="addRiskRow()">+ Add Risk</button>
                        </div>
                        <table class="opp-editable-table" id="risksTable">
                            <thead>
                                <tr>
                                    <th>Risk Description</th>
                                    <th>Probability</th>
                                    <th>Impact</th>
                                    <th>Mitigation</th>
                                    <th>Owner</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="risksTableBody">
                                <tr><td colspan="6" style="text-align: center; color: #999;">No risks identified yet</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Bid Decision Phase Tasks -->
                    <div class="opp-section">
                        <div class="opp-section-header">
                            <h3><span class="section-icon">📋</span> Phase Tasks</h3>
                            <button class="opp-table-btn add" onclick="openTaskModalForOpportunity(currentWorkspaceOppId, 'bid_decision')">+ Add Task</button>
                        </div>
                        <table class="opp-editable-table" id="bidTasksTable">
                            <thead><tr><th>Task</th><th>Assignee</th><th>Due Date</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="bidTasksTableBody">
                                <tr><td colspan="6" style="text-align:center;color:#999;">No tasks yet</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Final Bid Decision Section -->
                    <div class="opp-gate-decision" style="border-color: #28a745;">
                        <h4>⚖️ Final Bid Decision</h4>
                        <div class="opp-form-grid">
                            <div class="opp-form-group">
                                <label>Recommendation</label>
                                <select id="bidRecommendation" onchange="updateBidRecommendationUI()">
                                    <option value="pending">Pending</option>
                                    <option value="go">GO - Recommend Bid</option>
                                    <option value="no_go">NO-GO - Do Not Bid</option>
                                    <option value="conditional">CONDITIONAL - With Conditions</option>
                                </select>
                            </div>
                            <div class="opp-form-group" id="bidConditionsGroup" style="display: none;">
                                <label>Conditions for GO</label>
                                <textarea id="bidConditions" placeholder="List conditions that must be met..."></textarea>
                            </div>
                            <div class="opp-form-group full-width">
                                <label>Justification</label>
                                <textarea id="bidJustification" placeholder="Reasoning behind the recommendation..."></textarea>
                            </div>
                        </div>
                        <div class="opp-gate-buttons" style="margin-top: 20px;">
                            <div class="opp-gate-btn pursue" data-decision="go" onclick="selectFinalDecision('go')">
                                <div class="gate-icon">🚀</div>
                                <div class="gate-label">GO - Bid</div>
                            </div>
                            <div class="opp-gate-btn no-bid" data-decision="no_go" onclick="selectFinalDecision('no_go')">
                                <div class="gate-icon">🛑</div>
                                <div class="gate-label">NO-GO</div>
                            </div>
                        </div>
                        <div id="bidPostDecisionActions" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">
                            <button class="opp-btn opp-btn-success" id="convertToProposalBtn" onclick="convertToProposal()" style="display: none;">
                                📄 Convert to Proposal
                            </button>
                            <div id="noGoFields" style="display: none;">
                                <div class="opp-form-group">
                                    <label>Lessons Learned</label>
                                    <textarea id="bidLessonsLearned" placeholder="What did we learn from this opportunity?"></textarea>
                                </div>
                                <div class="opp-form-group">
                                    <label>Revisit Date (optional)</label>
                                    <input type="date" id="bidRevisitDate">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="opp-workspace-actions">
                        <button class="opp-btn opp-btn-secondary" onclick="switchWorkspacePhase('capture')">← Back</button>
                        <button class="opp-btn opp-btn-primary" onclick="saveBidDecisionData()">💾 Save Bid Decision</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Jira Integration Module -->
    <script src="js/jira_module.js?v=3"></script>
</body>
</html>
