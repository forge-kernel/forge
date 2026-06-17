<?php

$layoutSections = [
    'breadcrumbs' => component(name: 'ForgeComponents:admin/breadcrumbs', props: new \App\Modules\ForgeComponents\Definitions\Admin\BreadcrumbsDefinition(items: [
        new \App\Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition(label: 'Home', href: '/dashboard'),
        new \App\Modules\ForgeComponents\Definitions\Admin\BreadcrumbItemDefinition(label: 'Dashboard', active: true),
    ]))
];
?>

<div class="fc-admin-stack">
  <div>
    <h1 class="fc-admin-page-title">Dashboard</h1>
    <p class="fc-admin-text-muted">Welcome back, John. Here is what is happening today.</p>
  </div>

  <div class="fc-admin-grid fc-admin-grid--3">
    <div class="fc-card">
      <div class="fc-card__body">
        <div class="fc-admin-text-muted" style="margin-bottom:0.25rem">Total Revenue</div>
        <div style="font-size:1.5rem;font-weight:700">$45,231</div>
        <div style="font-size:0.75rem;color:var(--fc-color-success);margin-top:0.25rem">+12.5% from last month</div>
      </div>
    </div>


  </div>

  <div class="fc-admin-grid fc-admin-grid--2">
    <div class="fc-card">
      <div class="fc-card__header">
        <h3 class="fc-admin-card-title">Recent Activity</h3>
      </div>
      <div class="fc-card__body">
        <div class="fc-admin-stack fc-admin-stack--compact">
          <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem 0">
            <div style="width:0.5rem;height:0.5rem;border-radius:50%;background:var(--fc-color-success);flex-shrink:0"></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:0.875rem;font-weight:500">New user registered</div>
              <div style="font-size:0.75rem;color:var(--fc-color-text-muted)">2 minutes ago</div>
            </div>
          </div>


        </div>
      </div>
    </div>

  </div>
</div>
