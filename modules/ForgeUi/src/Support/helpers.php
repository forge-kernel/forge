<?php

if (!function_exists('modal')) {
    function modal(string $id, string $title = '', string $message = '', array $options = []): void
    {
        $defaults = [
            'size' => 'md',
            'closable' => true,
            'backdrop' => true,
            'confirmText' => 'Confirm',
            'cancelText' => 'Cancel',
            'showCancel' => true,
            'confirmAction' => null,
            'cancelAction' => null,
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'title' => $title,
            'message' => $message,
            'size' => $options['size'],
            'closable' => $options['closable'],
            'backdrop' => $options['backdrop'],
            'confirmText' => $options['confirmText'],
            'cancelText' => $options['cancelText'],
            'showCancel' => $options['showCancel'],
            'confirmAction' => $options['confirmAction'],
            'cancelAction' => $options['cancelAction'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('openModal', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:openModal', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('close_modal')) {
    function close_modal(?string $id = null): void
    {
        $data = $id ? ['id' => $id] : [];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('closeModal', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:closeModal', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('notification')) {
    function notification(string $message, string $type = 'info', int $duration = 5000): void
    {
        $data = [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('showNotification', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:showNotification', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('tooltip')) {
    function tooltip(string $target, string $content, array $options = []): void
    {
        $defaults = [
            'position' => 'auto',
            'delay' => 200,
            'trigger' => 'hover',
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'target' => $target,
            'content' => $content,
            'position' => $options['position'],
            'delay' => $options['delay'],
            'trigger' => $options['trigger'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('showTooltip', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:showTooltip', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('hide_tooltip')) {
    function hide_tooltip(?string $target = null): void
    {
        $data = $target ? ['target' => $target] : [];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('hideTooltip', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:hideTooltip', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('dropdown')) {
    function dropdown(string $id, array $items, array $options = []): void
    {
        $data = [
            'id' => $id,
            'items' => $items,
            ...$options,
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('createDropdown', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:createDropdown', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('open_dropdown')) {
    function open_dropdown(string $id, string $target): void
    {
        $data = ['id' => $id, 'target' => $target];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('openDropdown', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:openDropdown', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('close_dropdown')) {
    function close_dropdown(?string $id = null): void
    {
        $data = $id ? ['id' => $id] : [];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('closeDropdown', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:closeDropdown', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('tabs')) {
    function tabs(string $id, array $tabItems, array $options = []): void
    {
        $defaults = [
            'orientation' => 'horizontal',
            'defaultActive' => 0,
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'tabItems' => $tabItems,
            'orientation' => $options['orientation'],
            'defaultActive' => $options['defaultActive'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('createTabs', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:createTabs', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('switch_tab')) {
    function switch_tab(string $id, int $index): void
    {
        $data = ['id' => $id, 'index' => $index];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('switchTab', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:switchTab', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('accordion')) {
    function accordion(string $id, array $items, array $options = []): void
    {
        $defaults = [
            'allowMultiple' => false,
            'openItems' => [],
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'items' => $items,
            'allowMultiple' => $options['allowMultiple'],
            'openItems' => $options['openItems'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('createAccordion', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:createAccordion', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('toggle_accordion')) {
    function toggle_accordion(string $id, int $index): void
    {
        $data = ['id' => $id, 'index' => $index];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('toggleAccordion', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:toggleAccordion', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('show_loading')) {
    function show_loading(string $id, array $options = []): void
    {
        $defaults = [
            'type' => 'spinner',
            'overlay' => false,
            'fullPage' => false,
            'message' => '',
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'type' => $options['type'],
            'overlay' => $options['overlay'],
            'fullPage' => $options['fullPage'],
            'message' => $options['message'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('showLoading', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:showLoading', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('hide_loading')) {
    function hide_loading(?string $id = null): void
    {
        $data = $id ? ['id' => $id] : [];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('hideLoading', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:hideLoading', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('progress')) {
    function progress(string $id, float $value, array $options = []): void
    {
        $defaults = [
            'type' => 'linear',
            'showLabel' => true,
            'max' => 100,
            'color' => 'blue',
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'value' => $value,
            'type' => $options['type'],
            'showLabel' => $options['showLabel'],
            'max' => $options['max'],
            'color' => $options['color'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('createProgress', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:createProgress', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('update_progress')) {
    function update_progress(string $id, float $value): void
    {
        $data = ['id' => $id, 'value' => $value];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('updateProgress', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:updateProgress', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('toast')) {
    function toast(string $message, string $type = 'info', int $duration = 3000): void
    {
        $data = [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('showToast', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:showToast', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('drawer')) {
    function drawer(string $id, string $content, array $options = []): void
    {
        $defaults = [
            'position' => 'right',
            'width' => '400px',
            'height' => '400px',
            'title' => '',
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'id' => $id,
            'content' => $content,
            'position' => $options['position'],
            'width' => $options['width'],
            'height' => $options['height'],
            'title' => $options['title'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('openDrawer', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:openDrawer', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('close_drawer')) {
    function close_drawer(?string $id = null): void
    {
        $data = $id ? ['id' => $id] : [];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('closeDrawer', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:closeDrawer', {detail: " . json_encode($data) . "}));</script>";
    }
}

if (!function_exists('popover')) {
    function popover(string $target, string $content, array $options = []): void
    {
        $defaults = [
            'position' => 'auto',
            'trigger' => 'click',
        ];
        
        $options = array_merge($defaults, $options);
        
        $data = [
            'target' => $target,
            'content' => $content,
            'position' => $options['position'],
            'trigger' => $options['trigger'],
        ];
        
        if (class_exists('\App\Modules\ForgeWire\Support\ForgeWireResponse')) {
            $context = \App\Modules\ForgeWire\Support\ForgeWireResponse::getContext(
                \App\Modules\ForgeWire\Support\ForgeWireResponse::getCurrentContextId()
            );
            if ($context) {
                $context->addEvent('showPopover', $data);
                return;
            }
        }
        
        echo "<script>window.dispatchEvent(new CustomEvent('fw:event:showPopover', {detail: " . json_encode($data) . "}));</script>";
    }
}