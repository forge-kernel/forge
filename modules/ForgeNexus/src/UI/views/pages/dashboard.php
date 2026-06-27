<?php
/** @var array $data */
layout(name: "nexus", fromModule: true);
?>

<!-- Top row cards -->
<section class="card metric-card" <?= fw_id('metric-users') ?>>
    <div class="card-content">
        <h2 class="card-title">Total Users</h2>
        <p class="metric" fw:target>
            <?= $usersCount ?>
        </p>
        <p class="metric-change positive">+12% from last month</p>
    </div>
    <div class="card-icon" fw:click="refreshUsersCount">
        <i class="fa-solid fa-users"></i>
    </div>
</section>
<section class="card metric-card" <?= fw_id('metric-revenue') ?>>
    <div class="card-content">
        <h2 class="card-title">Revenue</h2>
        <p class="metric" fw:target>$45,678</p>
        <p class="metric-change positive">+8% from last month</p>
    </div>
    <div class="card-icon">
        <i class="fa-solid fa-dollar-sign"></i>
    </div>
</section>

<section class="card metric-card">
    <div class="card-content">
        <h2 class="card-title">Active Projects</h2>
        <p class="metric">42</p>
        <p class="metric-change negative">-3% from last month</p>
    </div>
    <div class="card-icon">
        <i class="fa-solid fa-folder-open"></i>
    </div>
</section>

<!-- Chart section -->
<section class="card chart-card">
    <header class="card-header">
        <h2 class="card-title">Performance Overview</h2>
        <div class="card-actions">
            <button class="card-action-button">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>
    </header>
    <div class="card-body">
        <div class="chart-placeholder">
            <!-- Chart would be rendered here with a JS library -->
            <div class="placeholder-text">Chart Visualization</div>
        </div>
    </div>
</section>

<!-- Recent activity section -->
<section class="card activity-card">
    <header class="card-header">
        <h2 class="card-title">Recent Activity</h2>
        <div class="card-actions">
            <button class="card-action-button">View All</button>
        </div>
    </header>
    <div class="card-body">
        <ul class="activity-list">
            <li class="activity-item">
                <div class="activity-icon">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-text"><strong>John Doe</strong> joined the team</p>
                    <p class="activity-time">2 hours ago</p>
                </div>
            </li>
            <li class="activity-item">
                <div class="activity-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-text"><strong>Sarah Smith</strong> created a new project</p>
                    <p class="activity-time">5 hours ago</p>
                </div>
            </li>
            <li class="activity-item">
                <div class="activity-icon">
                    <i class="fa-solid fa-code-commit"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-text"><strong>Mike Johnson</strong> pushed 24 commits</p>
                    <p class="activity-time">Yesterday</p>
                </div>
            </li>
        </ul>
    </div>
</section>