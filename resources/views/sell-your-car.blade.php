@extends('layouts.app')

@section('title', __('messages.pages.sell_your_car.page_title') . ' - Bilskyen')

@push('styles')
<style>
    /* Expandable Section Styles */
    .expandable-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        margin-bottom: 3rem;
        /* visible so manual combobox panels are not clipped at card edges */
        overflow: visible;
        transition: all 0.3s ease;
        /* box-shadow: 0 2px 8px oklch(0 0 0 / 0.05); */
    }
    
    .expandable-section:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.05);
    }
    
    .dark .expandable-section:hover {
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .section-header {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--muted);
        transition: background-color 0.2s;
        user-select: none;
    }
    
    .section-header:hover {
        background: var(--accent);
    }
    
    .section-header.active {
        background: var(--muted);
        color: var(--foreground);
    }
    
    .section-title-group {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex: 1;
    }
    
    .section-number {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--background);
        color: var(--foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.625rem;
        flex-shrink: 0;
    }
    
    .section-header.active .section-number {
        background: var(--background);
        color: var(--foreground);
    }
    
    .section-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.2;
    }
    
    .section-header.active .section-title {
        color: var(--foreground);
    }
    
    .section-subtitle {
        font-size: 0.625rem;
        color: var(--muted-foreground);
        margin-top: 0.125rem;
        line-height: 1.2;
    }
    
    .section-header.active .section-subtitle {
        color: var(--muted-foreground);
        opacity: 1;
    }
    
    .section-icon {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }
    
    .section-header.active .section-icon {
        transform: rotate(180deg);
    }
    
    .section-content {
        max-height: 5000px;
        overflow: hidden;
        transition: max-height 0.4s ease, padding 0.3s ease;
        padding: 1rem;
    }
    
    .section-content.expanded {
        max-height: 5000px;
        padding: 1rem;
        overflow: visible;
    }
    
    .section-content.collapsed {
        max-height: 0;
        padding: 0 1rem;
        overflow: hidden;
    }
    
    .section-description {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    
    /* Required Field Indicator */
    .required-field::after {
        content: ' *';
        color: var(--destructive);
    }
    
    /* Field Help Text */
    .field-help {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }
    
    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.875rem;
    }
    
    @media (min-width: 640px) {
        .form-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .form-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* 12-column layout: 3 fields per row, each field = 4/12 */
    .manual-entry-grid {
        display: grid;
        gap: 0.875rem;
        grid-template-columns: 1fr;
        align-items: start;
    }

    @media (min-width: 768px) {
        .manual-entry-grid {
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .manual-entry-grid > .manual-combobox,
        .manual-entry-grid > .manual-year-field,
        .manual-entry-grid > .manual-color-field {
            grid-column: span 4;
        }
    }

    .manual-year-field select,
    .manual-color-field select {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }

    .manual-year-field,
    .manual-color-field {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
    }

    .manual-combobox-trigger .dropdown-chevron {
        transition: transform 0.2s ease;
    }

    .manual-combobox-trigger[aria-expanded="true"] .dropdown-chevron {
        transform: rotate(180deg);
    }

    /* Above sibling fields and the next expandable section */
    .manual-combobox .manual-combobox-panel {
        z-index: 200;
    }

    #manual-entry-fields.sell-plate-variant-strip > .manual-entry-lead {
        display: none;
    }

    /* After plate lookup: show variant, fuel (so fuel type can be corrected after variant), and colour; hide the rest */
    #manual-entry-fields.sell-plate-variant-strip [data-manual-field]:not([data-manual-field="variant"]):not([data-manual-field="fuel"]):not([data-manual-field="color"]) {
        display: none !important;
    }

    .basic-info-follow-grid {
        display: grid;
        gap: 0.875rem;
        grid-template-columns: 1fr;
        align-items: start;
    }

    @media (min-width: 768px) {
        .basic-info-follow-grid {
            grid-template-columns: repeat(12, minmax(0, 1fr));
        }

        .basic-info-follow-grid > .basic-info-follow-field {
            grid-column: span 4;
        }

        .basic-info-follow-grid > .basic-info-follow-field:only-child {
            grid-column: 1 / -1;
        }
    }
    
    /* Month/Year Field Pair - fields side by side within one grid column */
    .field-pair-inner {
        display: flex;
        gap: 0.5rem;
    }
    
    .field-pair-inner > div {
        flex: 1;
    }
    
    /* On mobile, stack month/year fields vertically */
    @media (max-width: 640px) {
        .field-pair-inner {
            flex-direction: column;
        }
    }
    
    /* License Plate Lookup Section */
    .lookup-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
    }
    
    .lookup-section:hover {
        box-shadow: 0 4px 16px rgba(0, 74, 173, 0.1);
    }
    
    .dark .lookup-section {
        background: var(--card);
        border-color: var(--border);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .dark .lookup-section:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 16px rgba(0, 74, 173, 0.2);
    }
    
    .lookup-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--foreground);
        letter-spacing: -0.02em;
    }
    
    .lookup-section p {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }
    
    .lookup-input-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .lookup-input-wrapper {
        flex: 1;
        min-width: 200px;
        position: relative;
    }
    
    .lookup-input-wrapper label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted-foreground);
        margin-bottom: 0.75rem;
        display: block;
    }
    
    .lookup-input-button-container {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .lookup-button {
        height: 3.75rem;
        padding: 0 1.5rem;
        background: var(--primary);
        color: var(--primary-foreground);
        border: 1px solid var(--primary);
        border-radius: 0.875rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .lookup-button:hover:not(:disabled) {
        background: var(--primary);
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 74, 173, 0.2);
    }
    
    .lookup-button:active:not(:disabled) {
        transform: translateY(0);
    }
    
    .lookup-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .lookup-button-icon {
        width: 20px;
        height: 20px;
    }
    
    .lookup-button-text {
        display: inline-block;
    }
    
    @media (max-width: 640px) {
        .lookup-input-button-container {
            flex-direction: column;
        }
        
        .lookup-button {
            width: 100%;
            justify-content: center;
        }
        
        .lookup-input-button-container > div {
            width: 100%;
        }
    }
    
    .lookup-input-wrapper input {
        width: 100%;
        height: 3.75rem;
        padding: 0 1.25rem 0 3.75rem;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: var(--background);
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.875rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        /* box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 0 0 0 rgba(0, 0, 0, 0); */
        outline: none;
    }
    
    .lookup-input-wrapper input::placeholder {
        color: var(--muted-foreground);
        font-weight: 400;
        text-transform: none;
        letter-spacing: normal;
        opacity: 0.6;
    }
    
    .lookup-input-wrapper input:hover {
        border-color: var(--border);
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.12), 0 0 0 0 rgba(0, 0, 0, 0);
        transform: translateY(-1px);
    }
    
    .lookup-input-wrapper input:focus {
        border-color: var(--border);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 0 0 3px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
        background: var(--background);
    }
    
    .dark .lookup-input-wrapper input {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3), 0 0 0 0 rgba(0, 0, 0, 0);
    }
    
    .dark .lookup-input-wrapper input:hover {
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4), 0 0 0 0 rgba(0, 0, 0, 0);
    }
    
    .dark .lookup-input-wrapper input:focus {
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 3px rgba(255, 255, 255, 0.05);
    }
    
    .lookup-input-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        color: var(--muted-foreground);
        pointer-events: none;
        z-index: 1;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .lookup-input-wrapper input:focus ~ .lookup-input-icon {
        color: var(--foreground);
        transform: translateY(-50%) scale(1.15);
    }
    
    .lookup-input-wrapper input:hover ~ .lookup-input-icon {
        color: var(--foreground);
        transform: translateY(-50%) scale(1.05);
    }
    
    .btn {
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    
    .btn-primary {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn-primary:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: var(--background);
        color: var(--primary-foreground);
        border: 1px solid var(--primary-foreground);
    }
    
    .btn-secondary:hover:not(:disabled) {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Success Badge */
    .success-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: oklch(0.95 0.1 145);
        border: 1px solid oklch(0.8 0.15 145);
        border-radius: 0.5rem;
        font-size: 0.75rem;
        color: oklch(0.4 0.2 145);
        margin-bottom: 0.75rem;
    }
    
    .dark .success-badge {
        background: oklch(0.3 0.1 145);
        border-color: oklch(0.5 0.15 145);
        color: oklch(0.7 0.2 145);
    }
    
    /* Loading State */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 74, 173, 0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dark .loading-overlay {
        background: rgba(0, 74, 173, 0.5);
    }
    
    .loading-content {
        background: var(--card);
        padding: 1.25rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.15);
    }
    
    .dark .loading-content {
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.4);
    }
    
    /* Submit Section */
    .submit-section {
        background: var(--muted);
        /* border: 1px solid var(--border); */
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-top: 1.25rem;
        text-align: center;
    }
    
    .submit-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .submit-section p {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
    }
    
    .btn-submit {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        background: var(--primary);
        color: var(--primary-foreground);
        border-radius: 0.5rem;
        font-weight: 600;
    }
    
    .btn-submit:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.15);
    }
    
    .dark .btn-submit:hover:not(:disabled) {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.4);
    }
    
    /* Field Error */
    .field-error {
        font-size: 0.75rem;
        color: var(--destructive);
        margin-top: 0.25rem;
    }
    
    /* Expand All Button */
    .expand-controls {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    
    .expand-btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        background: var(--muted);
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .expand-btn:hover {
        background: var(--accent);
    }
    
    /* Mobile Responsive */
    @media (max-width: 640px) {
        .lookup-section {
            padding: 1rem;
        }
        
        .lookup-input-group {
            flex-direction: column;
        }
        
        .lookup-input-wrapper {
            min-width: 100%;
        }
        
        .section-header {
            padding: 0.5rem 0.625rem;
        }
        
        .section-content.expanded {
            padding: 0.75rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .expand-controls {
            justify-content: stretch;
        }
        
        .expand-btn {
            flex: 1;
        }
    }
    
    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
    
    /* Hide form initially */
    .form-hidden {
        display: none;
    }
    
    .form-visible {
        display: block;
        animation: fadeInForm 0.5s ease-in;
    }
    
    @keyframes fadeInForm {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Equipment Section Styles - Matching vehicles.blade.php */
    .equipment-type-details {
        margin-bottom: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        overflow: hidden;
        background: var(--card);
    }
    
    .equipment-type-details:not([open]) .equipment-type-content {
        display: none;
    }
    
    .equipment-type-details[open] .equipment-type-icon {
        transform: rotate(180deg);
    }
    
    .equipment-type-details .equipment-type-toggle {
        list-style: none;
        padding: 0.75rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--foreground);
        background: var(--muted);
        transition: background 0.2s;
        user-select: none;
    }
    
    .equipment-type-details .equipment-type-toggle::-webkit-details-marker,
    .equipment-type-details .equipment-type-toggle::marker {
        display: none;
    }
    
    .equipment-type-details .equipment-type-toggle:hover {
        background: var(--accent);
    }
    
    .equipment-type-details .equipment-type-content {
        padding: 1rem;
        border-top: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    
    .equipment-type-toggle {
        cursor: pointer;
    }
    
    .equipment-type-icon {
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }
    
    .equipment-type-icon.rotate-180 {
        transform: rotate(180deg);
    }
    
    .equipment-type-content {
        transition: all 0.3s ease;
    }
    
    /* Image Upload Styles */
    .image-upload-area {
        margin-bottom: 1rem;
    }
    
    .image-upload-area.has-images .upload-dropzone {
        padding: 1rem;
        border-style: solid;
    }
    
    .image-upload-area.has-images .upload-content {
        flex-direction: row;
        gap: 0.75rem;
    }
    
    .image-upload-area.has-images .upload-icon {
        width: 24px;
        height: 24px;
    }
    
    .image-upload-area.has-images .upload-text {
        font-size: 0.75rem;
        margin: 0;
    }
    
    .image-upload-area.has-images .upload-hint {
        display: none;
    }
    
    .image-input {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
        overflow: hidden;
    }
    
    .upload-dropzone {
        border: 2px dashed var(--border);
        border-radius: 0.5rem;
        padding: 2rem 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--muted);
    }
    
    .upload-dropzone:hover {
        border-color: var(--primary);
        background: var(--accent);
    }
    
    .upload-dropzone.drag-over {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
    }
    
    .upload-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .upload-icon {
        color: var(--muted-foreground);
        transition: color 0.3s ease;
    }
    
    .upload-dropzone:hover .upload-icon,
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary);
    }
    
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary-foreground);
    }
    
    .upload-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
    }
    
    .upload-hint {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }
    
    .upload-dropzone.drag-over .upload-text,
    .upload-dropzone.drag-over .upload-hint {
        color: var(--primary-foreground);
    }
    
    /* Image Preview Container */
    .image-preview-container {
        margin-top: 1rem;
    }
    
    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }
    
    @media (min-width: 640px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }
    
    @media (min-width: 1024px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    .image-preview-item {
        position: relative;
        aspect-ratio: 4 / 3;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 2px solid var(--border);
        background: var(--muted);
        transition: all 0.2s ease;
        cursor: move;
        cursor: grab;
    }
    
    .image-preview-item:active {
        cursor: grabbing;
    }
    
    .image-preview-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.1);
    }
    
    .dark .image-preview-item:hover {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.3);
    }
    
    /* Drag and drop states */
    .image-preview-item.dragging {
        opacity: 0.5;
        transform: scale(0.95);
        cursor: grabbing;
        z-index: 1000;
    }
    
    .image-preview-item.drag-over {
        border-color: var(--primary);
        border-width: 3px;
        transform: scale(1.05);
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.15);
    }
    
    .dark .image-preview-item.drag-over {
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.4);
    }
    
    /* Drag handle */
    .image-drag-handle {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        padding: 0.25rem;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: 10;
        pointer-events: none;
    }
    
    .image-preview-item:hover .image-drag-handle {
        opacity: 1;
    }
    
    .image-preview-item.dragging .image-drag-handle {
        opacity: 1;
    }
    
    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    .image-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0, 74, 173, 0.6) 100%);
        opacity: 0;
        transition: opacity 0.2s ease;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0.5rem;
    }
    
    .image-preview-item:hover .image-preview-overlay {
        opacity: 1;
    }
    
    .image-remove-btn {
        background: var(--destructive);
        color: white;
        border: none;
        border-radius: 0.375rem;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .image-remove-btn:hover {
        background: oklch(0.5 0.25 27);
        transform: scale(1.1);
    }
    
    .image-preview-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 74, 173, 0.7);
        color: white;
        padding: 0.5rem;
        font-size: 0.75rem;
        text-align: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .image-preview-item:hover .image-preview-info {
        opacity: 1;
    }
    
    .image-preview-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--muted-foreground);
    }
    
    /* Error Container - adapts to dark/light mode */
    .error-container {
        border-color: oklch(0.8 0.2 27) !important;
        background: oklch(0.95 0.1 27) !important;
        color: oklch(0.4 0.2 27) !important;
    }
    
    .dark .error-container {
        border-color: oklch(0.6 0.2 27) !important;
        background: oklch(0.3 0.1 27) !important;
        color: oklch(0.7 0.2 27) !important;
    }
    
    /* Override Tailwind red border classes for dark mode */
    .dark .border-red-500 {
        border-color: oklch(0.6 0.25 27) !important;
    }
    
    .dark .border-red-200 {
        border-color: oklch(0.5 0.15 27) !important;
    }
    
    .dark .bg-red-50 {
        background-color: oklch(0.3 0.1 27) !important;
    }
    
    .dark .text-red-800 {
        color: oklch(0.7 0.2 27) !important;
    }
    
    /* Servicebog Radio Buttons Styled as Buttons */
    .servicebog-radio {
        transition: all 0.2s ease;
    }
    
    .servicebog-radio:hover {
        background: var(--accent);
        border-color: var(--primary);
    }
    
    .servicebog-radio input[type="radio"]:checked + span {
        font-weight: 600;
        color: var(--primary);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) {
        background: var(--primary);
        border-color: var(--primary);
        color: var(--primary-foreground);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) span {
        color: var(--primary-foreground);
        font-weight: 600;
    }
    
    /* Vehicle Info Display - Compact with Icons */
    .vehicle-info-display {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        padding: 1rem;
        background: var(--muted);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
    }
    
    @media (min-width: 640px) {
        .vehicle-info-display {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    .vehicle-info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        background: var(--background);
        border-radius: 0.375rem;
        transition: all 0.2s ease;
    }
    
    .vehicle-info-item:hover {
        background: var(--accent);
    }
    
    .vehicle-info-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        color: var(--primary);
    }
    
    .vehicle-info-content {
        flex: 1;
        min-width: 0;
    }
    
    .vehicle-info-label {
        font-size: 0.625rem;
        color: var(--muted-foreground);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.125rem;
    }
    
    .vehicle-info-value {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--foreground);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .vehicle-info-value:empty::before {
        content: '—';
        color: var(--muted-foreground);
        font-weight: 400;
    }
    
    /* Plan Cards Styles */
    .plan-card {
        border: 2px solid var(--border);
        border-radius: 0.5rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: var(--card);
        position: relative;
    }
    
    .plan-card:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.1);
    }
    
    .plan-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    
    .plan-card:has(input[type="radio"]:checked) {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.15);
    }
    
    .plan-card:has(input[type="radio"]:checked) .plan-name {
        color: var(--primary-foreground);
    }
    
    .plan-card:has(input[type="radio"]:checked) .plan-description,
    .plan-card:has(input[type="radio"]:checked) .plan-features-label,
    .plan-card:has(input[type="radio"]:checked) .plan-feature-item {
        color: var(--primary-foreground);
        opacity: 0.95;
    }
    
    .plan-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }
    
    .plan-description {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-bottom: 0.75rem;
    }
    
    .plan-features-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--muted-foreground);
        margin-bottom: 0.5rem;
    }
    
    .plan-feature-item {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }
    
    .plans-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 1rem;
    }
    
    @media (min-width: 640px) {
        .plans-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .plans-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    /* Location Autocomplete Styles */
    .location-autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        margin-top: 0.25rem;
    }
    
    .location-autocomplete-dropdown.show {
        display: block;
    }
    
    .location-autocomplete-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        border-bottom: 1px solid var(--border);
    }
    
    .location-autocomplete-item:last-child {
        border-bottom: none;
    }
    
    .location-autocomplete-item:hover,
    .location-autocomplete-item.active {
        background: var(--accent);
    }
    
    .location-autocomplete-item-text {
        font-size: 0.875rem;
        color: var(--foreground);
    }
</style>
@endpush

@section('content')
<div class="container py-3 md:py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight mb-2">
            {{ __('messages.pages.sell_your_car.title') }}
        </h1>
        <p class="text-muted-foreground">
            {{ __('messages.pages.sell_your_car.description') }}
        </p>
    </div>

    @if(session('success'))
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.15 145); background: oklch(0.95 0.1 145); color: oklch(0.4 0.2 145);">
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.5 0.15 145) !important;
                background: oklch(0.3 0.1 145) !important;
                color: oklch(0.7 0.2 145) !important;
            }
        </style>
    @endif

    @if($errors->any())
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.2 27); background: oklch(0.95 0.1 27); color: oklch(0.4 0.2 27);">
            <p class="text-sm font-medium mb-2">{{ __('messages.pages.sell_your_car.fix_errors') }}</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.6 0.2 27) !important;
                background: oklch(0.3 0.1 27) !important;
                color: oklch(0.7 0.2 27) !important;
            }
        </style>
    @endif

    <!-- License Plate Lookup Section -->
    <div class="lookup-section bg-gray-50">
        <h2 class="text-lg font-semibold">{{ __('messages.pages.sell_your_car.find_vehicle_title') }}</h2>
        <p class="text-muted-foreground">{{ __('messages.pages.sell_your_car.find_vehicle_description') }}</p>
        
        <div class="lookup-input-group">
            <div class="lookup-input-wrapper">
                <label for="registration-lookup">
                    {{ __('messages.pages.sell_your_car.license_plate_number') }}
                </label>
                <div class="lookup-input-button-container">
                    <div style="position: relative; flex: 1;">
                        <svg class="lookup-input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            type="text"
                            id="registration-lookup"
                            placeholder="{{ __('messages.pages.sell_your_car.license_plate_placeholder') }}"
                            autocomplete="off"
                            spellcheck="false"
                        />
                    </div>
                    <button type="button" id="lookup-button" class="lookup-button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="lookup-button-icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="lookup-button-text">{{ __('messages.pages.sell_your_car.find_vehicle_button') }}</span>
                    </button>
                    <button type="button" id="enter-manually-button" class="lookup-button btn-secondary text-primary border border-primary" style="margin-left: 0;">
                        <span class="lookup-button-text">{{ __('messages.pages.sell_your_car.enter_manually') }}</span>
                    </button>
                </div>
                <p class="text-xs mt-2 text-muted-foreground">{{ __('messages.pages.sell_your_car.enter_manually_lead') }}</p>
                <p class="text-xs mt-2" id="lookup-error" style="opacity: 0.8; min-height: 1.25rem;"></p>
            </div>
        </div>
        
        <div id="lookup-loading" class="hidden mt-4">
            <div class="flex items-center gap-2 text-sm" style="opacity: 0.9;">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ __('messages.pages.sell_your_car.loading_vehicle_info') }}</span>
            </div>
        </div>
    </div>

    <!-- Vehicle Form -->
    <form id="vehicle-form" data-action="{{ route('sell-your-car.store') }}" enctype="multipart/form-data" class="form-hidden">
        @csrf

        <!-- Error Display Container -->
        <div id="form-errors-top" class="hidden w-full rounded-md border p-3 mb-4 error-container"></div>
        <div id="start-over-container" class="hidden mb-4">
            <a href="#" id="start-over-link" class="text-sm text-primary hover:underline">{{ __('messages.pages.sell_your_car.start_over') }}</a>
            <span class="text-sm text-muted-foreground ml-2">— {{ __('messages.pages.sell_your_car.start_over_lead') }}</span>
        </div>

        <!-- Section 1: Basic Vehicle Information -->
        <div class="expandable-section" data-section="basic-info">
            <div class="section-header active" onclick="toggleSection('basic-info')">
                <div class="section-title-group">
                    <div class="section-number">1</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_basic_info_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_basic_info_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    {{ __('messages.pages.sell_your_car.section_basic_info_description') }}
                </div>
                
                <!-- Vehicle Info Display -->
                <div id="vehicle-info-display" class="vehicle-info-display hidden">
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">{{ __('messages.forms.brand') }}</div>
                            <div class="vehicle-info-value" id="vehicle-brand-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">{{ __('messages.forms.model') }}</div>
                            <div class="vehicle-info-value" id="vehicle-model-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">{{ __('messages.pages.sell_your_car.year') }}</div>
                            <div class="vehicle-info-value" id="vehicle-year-display"></div>
                        </div>
                    </div>
                    <div class="vehicle-info-item">
                        <svg class="vehicle-info-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <div class="vehicle-info-content">
                            <div class="vehicle-info-label">{{ __('messages.forms.fuel_type') }}</div>
                            <div class="vehicle-info-value" id="vehicle-fuel-display"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Manual entry: combobox pattern (same idea as vehicles sidebar); options load from /api/v1/dmr/* -->
                <div id="manual-entry-fields" class="hidden mb-4">
                    <p class="manual-entry-lead text-sm text-muted-foreground mb-3">{{ __('messages.pages.sell_your_car.manual_entry_lead') }}</p>
                    <div class="manual-entry-grid">
                        <div class="manual-combobox space-y-2" data-manual-field="brand">
                            <label for="manual-brand-trigger" class="text-sm font-medium required-field">{{ __('messages.forms.brand') }}</label>
                            <div class="relative">
                                <button type="button" id="manual-brand-trigger" class="manual-combobox-trigger flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-left items-center justify-between gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="manual-combobox-label truncate text-muted-foreground">{{ __('messages.pages.sell_your_car.select_brand') }}</span>
                                    <svg class="dropdown-chevron flex-shrink-0 w-4 h-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div id="manual-brand-panel" class="manual-combobox-panel hidden absolute left-0 right-0 top-full z-50 mt-1 rounded-md border border-input bg-background shadow-lg max-h-56 overflow-hidden flex flex-col">
                                    <div class="p-2 border-b border-border">
                                        <input type="text" id="manual-brand-panel-search" placeholder="{{ __('messages.pages.sell_your_car.search_brands') }}" autocomplete="off" spellcheck="false" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    </div>
                                    <div id="manual-brand-list" class="overflow-y-auto p-2 space-y-0.5 max-h-44"></div>
                                </div>
                                <select id="manual_brand_id" class="sr-only" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_brand') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="manual-combobox space-y-2" data-manual-field="model">
                            <label for="manual-model-trigger" class="text-sm font-medium required-field">{{ __('messages.forms.model') }}</label>
                            <div class="relative">
                                <button type="button" id="manual-model-trigger" disabled class="manual-combobox-trigger flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-left items-center justify-between gap-2 opacity-60 cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="manual-combobox-label truncate text-muted-foreground">{{ __('messages.pages.sell_your_car.select_model') }}</span>
                                    <svg class="dropdown-chevron flex-shrink-0 w-4 h-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div id="manual-model-panel" class="manual-combobox-panel hidden absolute left-0 right-0 top-full z-50 mt-1 rounded-md border border-input bg-background shadow-lg max-h-56 overflow-hidden flex flex-col">
                                    <div class="p-2 border-b border-border">
                                        <input type="text" id="manual-model-panel-search" placeholder="{{ __('messages.pages.sell_your_car.search_models') }}" autocomplete="off" spellcheck="false" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    </div>
                                    <div id="manual-model-list" class="overflow-y-auto p-2 space-y-0.5 max-h-44"></div>
                                </div>
                                <select id="manual_model_id" class="sr-only" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_model') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="manual-combobox space-y-2" data-manual-field="variant">
                            <label for="manual-variant-trigger" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.variant_label') }}</label>
                            <div class="relative">
                                <button type="button" id="manual-variant-trigger" disabled class="manual-combobox-trigger flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-left items-center justify-between gap-2 opacity-60 cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="manual-combobox-label truncate text-muted-foreground">{{ __('messages.pages.sell_your_car.select_variant') }}</span>
                                    <svg class="dropdown-chevron flex-shrink-0 w-4 h-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div id="manual-variant-panel" class="manual-combobox-panel hidden absolute left-0 right-0 top-full z-50 mt-1 rounded-md border border-input bg-background shadow-lg max-h-56 overflow-hidden flex flex-col">
                                    <div class="p-2 border-b border-border">
                                        <div class="relative">
                                            <input type="text" id="manual-variant-panel-search" placeholder="{{ __('messages.pages.sell_your_car.search_variants') }}" autocomplete="off" spellcheck="false" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 pr-9 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                            <span id="manual_variant_loading" class="hidden absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true">
                                                <svg class="animate-spin h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </span>
                                        </div>
                                    </div>
                                    <div id="manual-variant-list" class="overflow-y-auto p-2 space-y-0.5 max-h-44"></div>
                                </div>
                                <select id="variant_id" disabled class="sr-only" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_variant') }}</option>
                                </select>
                                <input type="hidden" id="variant_id_hidden" name="variant_id" value="">
                            </div>
                            <p class="field-help">{{ __('messages.pages.sell_your_car.variant_help') }}</p>
                        </div>
                        <div class="manual-combobox space-y-2" data-manual-field="fuel">
                            <label for="manual-fuel-trigger" class="text-sm font-medium required-field">{{ __('messages.forms.fuel_type') }}</label>
                            <div class="relative">
                                <button type="button" id="manual-fuel-trigger" class="manual-combobox-trigger flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-left items-center justify-between gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="manual-combobox-label truncate text-muted-foreground">{{ __('messages.pages.sell_your_car.select_fuel_type') }}</span>
                                    <svg class="dropdown-chevron flex-shrink-0 w-4 h-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                </button>
                                <div id="manual-fuel-panel" class="manual-combobox-panel hidden absolute left-0 right-0 top-full z-50 mt-1 rounded-md border border-input bg-background shadow-lg max-h-56 overflow-hidden flex flex-col">
                                    <div class="p-2 border-b border-border">
                                        <input type="text" id="manual-fuel-panel-search" placeholder="{{ __('messages.pages.sell_your_car.search_fuel_types') }}" autocomplete="off" spellcheck="false" class="w-full h-9 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    </div>
                                    <div id="manual-fuel-list" class="overflow-y-auto p-2 space-y-0.5 max-h-44"></div>
                                </div>
                                <select id="manual_fuel_type_id" class="sr-only" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_fuel_type') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="manual-year-field space-y-2" data-manual-field="year">
                            <label for="manual_model_year_id" class="text-sm font-medium required-field">{{ __('messages.forms.model_year') }}</label>
                            <select id="manual_model_year_id"
                                class="block h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.pages.sell_your_car.select_model_year') }}</option>
                                @for($y = (int) date('Y'); $y >= 1975; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="manual-color-field space-y-2" data-manual-field="color">
                            <label for="colour_id" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.color_label') }}</label>
                            <select id="colour_id" name="colour_id"
                                class="block h-9 w-full min-w-0 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                <option value="">{{ __('messages.pages.sell_your_car.select_color') }}</option>
                                @foreach($lookupData['dmrColours'] as $color)
                                    <option value="{{ $color->id }}">{{ $color->name }}</option>
                                @endforeach
                            </select>
                            <p class="field-help">{{ __('messages.pages.sell_your_car.color_help') }}</p>
                        </div>
                    </div>
                </div>

                <div class="basic-info-follow-grid">
                    <div class="basic-info-follow-field space-y-2">
                        <label class="text-sm font-medium">{{ __('messages.pages.sell_your_car.title_label') }}</label>
                        <div id="title-display" class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm items-center text-muted-foreground min-h-[2.25rem]"></div>
                        <input type="hidden" id="title" name="title" value="">
                        <input type="hidden" id="brand_id" name="brand_id" value="">
                        <input type="hidden" id="model_id" name="model_id" value="">
                        <input type="hidden" id="model_year" name="model_year" value="">
                        <input type="hidden" id="fuel_type_id" name="fuel_type_id" value="">
                        <p class="field-help">{{ __('messages.pages.sell_your_car.title_help') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Vehicle Specifications -->
        <div class="expandable-section" data-section="specifications">
            <div class="section-header active" onclick="toggleSection('specifications')">
                <div class="section-title-group">
                    <div class="section-number">2</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_specifications_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_specifications_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    {{ __('messages.pages.sell_your_car.section_specifications_description') }}
                    </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium required-field">{{ __('messages.forms.km_driven') }}</label>
                        <input type="number" id="km_driven" name="km_driven" min="0" step="any" inputmode="decimal" required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                        <p class="field-help">{{ __('messages.pages.sell_your_car.km_driven_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="gear_type_id" class="text-sm font-medium required-field">{{ __('messages.forms.gear_type') }}</label>
                        <select id="gear_type_id" name="gear_type_id" required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">{{ __('messages.pages.sell_your_car.select_gear_type') }}</option>
                            @foreach($lookupData['gearTypes'] ?? [] as $gt)
                                <option value="{{ $gt->id }}">{{ $gt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="condition_id" class="text-sm font-medium">{{ __('messages.forms.condition') }}</label>
                        <select id="condition_id" name="condition_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            @foreach($lookupData['conditions'] ?? [] as $cond)
                                <option value="{{ $cond->id }}" @if((int) $cond->id === 2) selected @endif>{{ $cond->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ __('messages.pages.sell_your_car.first_registration') }}</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="first_registration_month" name="first_registration_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_month') }}</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ \Carbon\Carbon::createFromDate(null, $i, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="first_registration_year" name="first_registration_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_year') }}</option>
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">{{ __('messages.pages.sell_your_car.first_registration_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">{{ __('messages.pages.sell_your_car.last_inspection') }}</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="last_inspection_month" name="last_inspection_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_month') }}</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ \Carbon\Carbon::createFromDate(null, $i, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="last_inspection_year" name="last_inspection_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">{{ __('messages.pages.sell_your_car.select_year') }}</option>
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">{{ __('messages.pages.sell_your_car.last_inspection_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="km_per_liter" id="km_per_liter_label" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.fuel_efficiency_label') }}</label>
                        <input type="number" id="km_per_liter" name="km_per_liter" min="0" step="any" inputmode="decimal"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                        <p class="field-help" id="km_per_liter_help">{{ __('messages.pages.sell_your_car.fuel_efficiency_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="maximum_weight_kg" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.technical_total_weight') }}</label>
                        <input type="number" id="maximum_weight_kg" name="maximum_weight_kg" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        <p class="field-help">{{ __('messages.pages.sell_your_car.technical_total_weight_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="emission_norm_id" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.emission_standard_label') }}</label>
                        <select id="emission_norm_id" name="emission_norm_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">{{ __('messages.pages.sell_your_car.select_euronom') }}</option>
                            @foreach($lookupData['dmrEuronorms'] as $euronom)
                                <option value="{{ $euronom->id }}">{{ $euronom->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help">{{ __('messages.pages.sell_your_car.euronom_help') }}</p>
                    </div>
                    </div>
                    </div>
                    </div>

        <!-- Section 3: Equipment & Features -->
        <div class="expandable-section" data-section="equipment">
            <div class="section-header active" onclick="toggleSection('equipment')">
                <div class="section-title-group">
                    <div class="section-number">3</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_equipment_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_equipment_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    {{ __('messages.pages.sell_your_car.section_equipment_description') }}
                </div>
                
                <div id="sell-your-car-equipment-root" class="space-y-2">
                    @include('partials.sell-your-car-equipment', ['lookupData' => $lookupData])
                </div>
            </div>
        </div>

               
                
                <!-- Servicebog -->
                <div class="mb-4">
                    <label class="text-sm font-medium mb-2 block">{{ __('messages.pages.sell_your_car.servicebog') }}</label>
                    <div class="flex gap-2 md:gap-3">
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Yes" class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>{{ __('messages.common.yes') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="No" class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>{{ __('messages.common.no') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Default" checked class="h-3 w-3 md:h-4 md:w-4 text-primary">
                            <span>{{ __('messages.pages.sell_your_car.default') }}</span>
                        </label>
                    </div>
                    <p class="field-help mt-2">{{ __('messages.pages.sell_your_car.servicebog_help') }}</p>
                </div>

        <!-- Section 4: Pricing & Tax -->
        <div class="expandable-section" data-section="pricing">
            <div class="section-header active" onclick="toggleSection('pricing')">
                <div class="section-title-group">
                    <div class="section-number">4</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_pricing_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_pricing_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium required-field">{{ __('messages.pages.sell_your_car.price_label') }}</label>
                        <input type="number" id="price" name="price" required min="0" step="any" inputmode="decimal"
                            class="flex h-9 w-full rounded-md border {{ $errors->has('price') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                        @error('price')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-help">{{ __('messages.pages.sell_your_car.price_help') }}</p>
                    </div>

                    </div>

            </div>
        </div>

        <!-- Section 5: Media -->
        <div class="expandable-section" data-section="photos">
            <div class="section-header active" onclick="toggleSection('photos')">
                <div class="section-title-group">
                    <div class="section-number">5</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_photos_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_photos_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    {{ __('messages.pages.sell_your_car.section_photos_description') }}
                </div>
                <p class="field-help mb-3">{{ __('messages.pages.sell_your_car.photos_optional_hint') }}</p>
                
                <!-- Image Upload Area -->
                <div class="image-upload-area" id="image-upload-area">
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        multiple 
                        accept="image/*"
                        class="image-input"
                    >
                    <div class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-content">
                            <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="upload-text">{{ __('messages.pages.sell_your_car.upload_text') }}</p>
                            <p class="upload-hint">{{ __('messages.pages.sell_your_car.upload_hint') }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Image Preview Grid -->
                <div id="image-preview-container" class="image-preview-container hidden">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold">
                            {{ __('messages.pages.sell_your_car.selected_images') }} (<span id="image-count">0</span>)
                        </h4>
                        <button type="button" onclick="clearAllImages()" class="text-xs text-muted-foreground hover:text-foreground">
                            {{ __('messages.pages.sell_your_car.clear_all') }}
                        </button>
                    </div>
                    <div id="image-preview-grid" class="image-preview-grid">
                        <!-- Image previews will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Description -->
        <div class="expandable-section" data-section="description">
            <div class="section-header active" onclick="toggleSection('description')">
                <div class="section-title-group">
                    <div class="section-number">6</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_description_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_description_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                    <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">{{ __('messages.forms.message') }}</label>
                    <div class="flex items-center gap-2 mb-2">
                        <button type="button" id="ai-suggest-description-btn" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-accent">
                            {{ __('messages.pages.sell_your_car.ai_suggest_description') }}
                        </button>
                        <span id="ai-suggest-description-status" class="text-xs text-muted-foreground"></span>
                    </div>
                    <textarea id="description" name="description" rows="6"
                        class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="{{ __('messages.pages.sell_your_car.description_placeholder') }}"></textarea>
                    <p class="field-help">{{ __('messages.pages.sell_your_car.description_help') }}</p>
                </div>
            </div>
                    </div>

        <!-- Section 7: Seller Information -->
        <div class="expandable-section" data-section="seller-info">
            <div class="section-header active" onclick="toggleSection('seller-info')">
                <div class="section-title-group">
                    <div class="section-number">7</div>
                    <div>
                        <div class="section-title">{{ __('messages.pages.sell_your_car.section_seller_title') }}</div>
                        <div class="section-subtitle">{{ __('messages.pages.sell_your_car.section_seller_subtitle') }}</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="seller_phone" class="text-sm font-medium">{{ __('messages.forms.phone') }}</label>
                        <input type="text" id="seller_phone" name="seller_phone" value="{{ $user->phone ?? '' }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="{{ __('messages.pages.sell_your_car.phone_placeholder') }}">
                        <p class="field-help">{{ __('messages.pages.sell_your_car.phone_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_address" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.location_label') }}</label>
                        <div class="relative">
                            <input type="text" id="seller_address" name="seller_address" value="{{ $user->address ?? '' }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="{{ __('messages.pages.sell_your_car.address_placeholder') }}">
                            <div id="location-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">{{ __('messages.pages.sell_your_car.location_help') }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_postcode" class="text-sm font-medium">{{ __('messages.pages.sell_your_car.postal_code_label') }}</label>
                        <div class="relative">
                            <input type="text" id="seller_postcode" name="seller_postcode" value="{{ $user->postcode ?? '' }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="{{ __('messages.pages.sell_your_car.postal_code_placeholder') }}">
                            <div id="postcode-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">{{ __('messages.pages.sell_your_car.postal_code_help') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden fields for required data from API -->
        <input type="hidden" id="registration" name="registration" value="">
        <input type="hidden" id="dmr_fact_vehicle_id" name="dmr_fact_vehicle_id" value="">

        <!-- Submit Section -->
        <div class="submit-section">
            <h3>{{ __('messages.pages.sell_your_car.ready_to_publish') }}</h3>
            <p>{{ __('messages.pages.sell_your_car.ready_to_publish_description') }}</p>
            <button type="submit" class="btn btn-submit">
                {{ __('messages.pages.sell_your_car.publish_button') }}
            </button>
        </div>
    </form>
</div>

@php
    $sellYourCarTranslations = [
        'selectBrand' => __('messages.pages.sell_your_car.select_brand'),
        'selectModel' => __('messages.pages.sell_your_car.select_model'),
        'selectModelYear' => __('messages.pages.sell_your_car.select_model_year'),
        'selectFuelType' => __('messages.pages.sell_your_car.select_fuel_type'),
        'selectVariant' => __('messages.pages.sell_your_car.select_variant'),
        'selectColor' => __('messages.pages.sell_your_car.select_color'),
        'selectEmissionStandard' => __('messages.pages.sell_your_car.select_euronom'),
        'equipmentOther' => __('messages.pages.sell_your_car.equipment_other'),
        'lookupEnterRegistration' => __('messages.pages.sell_your_car.lookup_enter_registration'),
        'lookupFetchFailed' => __('messages.pages.sell_your_car.lookup_fetch_failed'),
        'lookupTimeout' => __('messages.pages.sell_your_car.lookup_timeout'),
        'lookupServiceUnavailable' => __('messages.pages.sell_your_car.lookup_service_unavailable'),
        'lookupNoVehicleData' => __('messages.pages.sell_your_car.lookup_no_vehicle_data'),
        'lookupMissingReference' => __('messages.pages.sell_your_car.lookup_missing_reference'),
        'lookupContextLoadFailed' => __('messages.pages.sell_your_car.lookup_context_load_failed'),
        'lookupSuccess' => __('messages.pages.sell_your_car.lookup_success'),
        'lookupGenericError' => __('messages.pages.sell_your_car.lookup_generic_error'),
        'requiredFieldError' => __('messages.pages.sell_your_car.required_field_error'),
        'imageRequiredError' => __('messages.pages.sell_your_car.image_required_error'),
        'manualEntryRequiredError' => __('messages.pages.sell_your_car.manual_entry_required_error'),
        'saving' => __('messages.pages.sell_your_car.saving'),
        'savingVehicle' => __('messages.pages.sell_your_car.saving_vehicle'),
        'imageUploadErrorTitle' => __('messages.pages.sell_your_car.image_upload_error_title'),
        'imageUploadFailedFriendly' => __('messages.pages.sell_your_car.image_upload_failed_friendly'),
        'imageInvalidFormat' => __('messages.pages.sell_your_car.image_invalid_format'),
        'imageTooLarge' => __('messages.pages.sell_your_car.image_too_large'),
        'imageAlreadySelected' => __('messages.pages.sell_your_car.image_already_selected'),
        'noValidImages' => __('messages.pages.sell_your_car.no_valid_images'),
        'saveMissingToken' => __('messages.pages.sell_your_car.save_missing_token'),
        'saveMissingRedirect' => __('messages.pages.sell_your_car.save_missing_redirect'),
        'unexpectedError' => __('messages.pages.sell_your_car.unexpected_error'),
        'fuelEfficiencyLabel' => __('messages.pages.sell_your_car.fuel_efficiency_label'),
        'fuelEfficiencyHelp' => __('messages.pages.sell_your_car.fuel_efficiency_help'),
        'electricRangeLabel' => __('messages.pages.sell_your_car.electric_range_label'),
        'electricRangeHelp' => __('messages.pages.sell_your_car.electric_range_help'),
        'hybridEfficiencyLabel' => __('messages.pages.sell_your_car.hybrid_efficiency_label'),
        'hybridEfficiencyHelp' => __('messages.pages.sell_your_car.hybrid_efficiency_help'),
        'aiSuggestLoading' => __('messages.pages.sell_your_car.ai_suggest_loading'),
        'aiSuggestFailed' => __('messages.pages.sell_your_car.ai_suggest_failed'),
    ];
@endphp

@push('scripts')
<script id="sell-your-car-locations-data" type="application/json">
@json($lookupData['locations'] ?? [])
</script>
<script id="sell-your-car-lookup-context-base-data" type="application/json">
@json(url('/sell-your-car/lookup-context'))
</script>
<script id="sell-your-car-translations-data" type="application/json">
@json($sellYourCarTranslations)
</script>
<script>
    const sellYourCarLocationsEl = document.getElementById('sell-your-car-locations-data');
    const sellYourCarLookupContextBaseEl = document.getElementById('sell-your-car-lookup-context-base-data');
    const sellYourCarTranslationsEl = document.getElementById('sell-your-car-translations-data');
    window.locationsData = sellYourCarLocationsEl ? JSON.parse(sellYourCarLocationsEl.textContent) : [];
    window.sellYourCarLookupContextBase = sellYourCarLookupContextBaseEl ? JSON.parse(sellYourCarLookupContextBaseEl.textContent) : '';
    window.sellYourCarTranslations = sellYourCarTranslationsEl ? JSON.parse(sellYourCarTranslationsEl.textContent) : {};
</script>
<script src="{{ asset('js/sell-your-car-form.js') }}"></script>
@endpush
@endsection
