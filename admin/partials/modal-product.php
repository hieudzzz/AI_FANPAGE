<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== MODAL: Product Add/Edit ===== -->
<div id="aif-product-modal" class="aif-modal">
    <div class="aif-modal-content">
        <div class="aif-modal-header">
            <div class="aif-modal-header-decor-1"></div>
            <div class="aif-modal-header-decor-2"></div>
            <div class="aif-modal-header-inner">
                <div class="aif-modal-header-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div>
                    <h3 id="modal-title">Thêm sản phẩm mới</h3>
                    <p>Thông tin sản phẩm giúp AI tư vấn khách hàng chuyên nghiệp hơn.</p>
                </div>
            </div>
            <button type="button" class="aif-modal-close"
                onclick="jQuery('#aif-product-modal').removeClass('active');">&#10005;</button>
        </div>
        <div class="aif-modal-body">
            <form id="aif-product-form">
                <input type="hidden" name="id" id="prod-id">

                <div class="aif-form-group">
                    <label class="aif-form-label">Tên sản phẩm <span class="aif-form-required">*</span></label>
                    <input type="text" name="product_name" id="prod-name" required
                        placeholder="Ví dụ: Áo thun nam Cotton Premium">
                </div>

                <div class="aif-form-row">
                    <div>
                        <label class="aif-form-label">Danh mục</label>
                        <input type="text" name="category" id="prod-cat" placeholder="Thời trang nam">
                    </div>
                    <div>
                        <label class="aif-form-label">SKU</label>
                        <input type="text" name="sku" id="prod-sku" placeholder="TSHIRT-001">
                    </div>
                </div>

                <div class="aif-form-row">
                    <div>
                        <label class="aif-form-label">Giá bán (VNĐ)</label>
                        <input type="text" name="price" id="prod-price" placeholder="250.000" class="aif-input-price">
                    </div>
                    <div>
                        <label class="aif-form-label">Trạng thái</label>
                        <input type="hidden" name="status" id="prod-status" value="active">
                        <div class="aif-status-toggle">
                            <button type="button" class="aif-status-btn aif-status-btn-active active" data-value="active">
                                <span class="aif-status-btn-dot"></span>
                                Đang kinh doanh
                            </button>
                            <button type="button" class="aif-status-btn aif-status-btn-inactive" data-value="inactive">
                                <span class="aif-status-btn-dot"></span>
                                Ngừng kinh doanh
                            </button>
                        </div>
                    </div>
                </div>

                <div class="aif-form-group">
                    <label class="aif-form-label">Mô tả đặc điểm <span class="aif-form-hint">(Dành cho
                            AI)</span></label>
                    <textarea name="description" id="prod-desc"
                        placeholder="Nhập thông số, ưu điểm nổi bật để AI tư vấn khách tốt hơn..."></textarea>
                </div>

                <div class="aif-modal-footer">
                    <button type="button" class="aif-btn aif-btn-ghost"
                        onclick="jQuery('#aif-product-modal').removeClass('active');">Hủy bỏ</button>
                    <button type="submit" class="aif-btn aif-btn-primary">
                        <span class="dashicons dashicons-saved"></span> Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
