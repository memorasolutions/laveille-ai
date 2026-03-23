<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@props(['activeKey' => ''])

<div style="width:100%;max-width:400px;background:#fff;border:1px solid #e0e0e0;border-radius:8px;font-family:system-ui,sans-serif;overflow:hidden;">
    <div style="padding:8px 12px;background:#1A1D23;color:#fff;font-size:11px;font-weight:600;">Aperçu du positionnement</div>

    <!-- Topbar -->
    <div style="height:16px;background:#0B7285;width:100%;"></div>

    <!-- Navigation -->
    <div style="height:32px;border-bottom:1px solid #eee;display:flex;align-items:center;padding:0 12px;justify-content:space-between;">
        <div style="width:28px;height:28px;background:#ddd;border-radius:50%;"></div>
        <div style="display:flex;gap:8px;">
            <div style="width:35px;height:6px;background:#ddd;border-radius:3px;"></div>
            <div style="width:35px;height:6px;background:#ddd;border-radius:3px;"></div>
            <div style="width:35px;height:6px;background:#ddd;border-radius:3px;"></div>
        </div>
    </div>

    <!-- header-leaderboard -->
    <div style="padding:8px 12px;">
        <div style="width:100%;height:36px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:9px;font-weight:700;border-radius:4px;
            background:{{ $activeKey === 'header-leaderboard' ? '#E67E22' : '#F6F7F9' }};
            color:{{ $activeKey === 'header-leaderboard' ? '#fff' : '#999' }};
            border:1px {{ $activeKey === 'header-leaderboard' ? 'solid #E67E22' : 'dashed #ccc' }};">
            <span>header-leaderboard</span><span style="font-weight:400;font-size:7px;">728×90</span>
        </div>
    </div>

    <!-- Hero -->
    <div style="height:70px;background:#eee;margin:0 12px 8px;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:10px;">Hero</div>

    <!-- Content 2 colonnes -->
    <div style="display:flex;padding:0 12px 12px;gap:8px;">
        <!-- Article -->
        <div style="width:63%;">
            <!-- article-top -->
            <div style="width:100%;height:28px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;border-radius:4px;
                background:{{ $activeKey === 'article-top' ? '#E67E22' : '#F6F7F9' }};
                color:{{ $activeKey === 'article-top' ? '#fff' : '#999' }};
                border:1px {{ $activeKey === 'article-top' ? 'solid #E67E22' : 'dashed #ccc' }};">
                article-top
            </div>

            <!-- Paragraphes -->
            <div style="margin-bottom:6px;">
                <div style="height:5px;background:#e0e0e0;margin-bottom:4px;width:100%;border-radius:2px;"></div>
                <div style="height:5px;background:#e0e0e0;margin-bottom:4px;width:88%;border-radius:2px;"></div>
                <div style="height:5px;background:#e0e0e0;margin-bottom:4px;width:94%;border-radius:2px;"></div>
            </div>

            <!-- article-inline -->
            <div style="width:100%;height:28px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;border-radius:4px;
                background:{{ $activeKey === 'article-inline' ? '#E67E22' : '#F6F7F9' }};
                color:{{ $activeKey === 'article-inline' ? '#fff' : '#999' }};
                border:1px {{ $activeKey === 'article-inline' ? 'solid #E67E22' : 'dashed #ccc' }};">
                article-inline
            </div>

            <!-- Paragraphes suite -->
            <div style="margin-bottom:6px;">
                <div style="height:5px;background:#e0e0e0;margin-bottom:4px;width:96%;border-radius:2px;"></div>
                <div style="height:5px;background:#e0e0e0;margin-bottom:4px;width:80%;border-radius:2px;"></div>
            </div>

            <!-- article-bottom -->
            <div style="width:100%;height:28px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;border-radius:4px;
                background:{{ $activeKey === 'article-bottom' ? '#E67E22' : '#F6F7F9' }};
                color:{{ $activeKey === 'article-bottom' ? '#fff' : '#999' }};
                border:1px {{ $activeKey === 'article-bottom' ? 'solid #E67E22' : 'dashed #ccc' }};">
                article-bottom
            </div>

            <div style="height:1px;background:#eee;margin-bottom:8px;"></div>

            <!-- between-posts -->
            <div style="width:100%;height:28px;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;border-radius:4px;
                background:{{ $activeKey === 'between-posts' ? '#E67E22' : '#F6F7F9' }};
                color:{{ $activeKey === 'between-posts' ? '#fff' : '#999' }};
                border:1px {{ $activeKey === 'between-posts' ? 'solid #E67E22' : 'dashed #ccc' }};">
                between-posts
            </div>
        </div>

        <!-- Sidebar -->
        <div style="width:37%;">
            <!-- sidebar-rectangle -->
            <div style="width:100%;height:75px;margin-bottom:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:8px;font-weight:700;border-radius:4px;
                background:{{ $activeKey === 'sidebar-rectangle' ? '#E67E22' : '#F6F7F9' }};
                color:{{ $activeKey === 'sidebar-rectangle' ? '#fff' : '#999' }};
                border:1px {{ $activeKey === 'sidebar-rectangle' ? 'solid #E67E22' : 'dashed #ccc' }};">
                <span>sidebar-rect</span><span style="font-weight:400;font-size:7px;">300×250</span>
            </div>
            <div style="background:#f5f5f5;height:40px;margin-bottom:6px;border-radius:4px;"></div>
            <div style="background:#f5f5f5;height:60px;border-radius:4px;"></div>
        </div>
    </div>

    <!-- footer-banner -->
    <div style="padding:0 12px 8px;">
        <div style="width:100%;height:36px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:9px;font-weight:700;border-radius:4px;
            background:{{ $activeKey === 'footer-banner' ? '#E67E22' : '#F6F7F9' }};
            color:{{ $activeKey === 'footer-banner' ? '#fff' : '#999' }};
            border:1px {{ $activeKey === 'footer-banner' ? 'solid #E67E22' : 'dashed #ccc' }};">
            <span>footer-banner</span><span style="font-weight:400;font-size:7px;">728×90</span>
        </div>
    </div>

    <!-- Footer -->
    <div style="height:30px;background:#1A1D23;border-radius:0 0 7px 7px;"></div>
</div>
