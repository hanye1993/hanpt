    <!-- 页面内容结束 -->
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ThinkPHP应用. 保留所有权利.</p>
        </div>
    </footer>
    
    <style>
    /* 页脚样式 */
    .footer {
        margin-left: var(--sidebar-width);
        padding: 20px 30px;
        text-align: center;
        color: var(--text-muted);
        border-top: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }
    
    @media (max-width: 992px) {
        .footer {
            margin-left: 0;
        }
        
        body.sidebar-open .footer {
            margin-left: var(--sidebar-width);
        }
    }
    </style>
</body>
</html> 