# Anpassbare Adminbar für Wordpress (Wordpress-Plugin)

## Initialisierung der Klasse
```php
$custom_adminbar = new Custom_AdminBar();
```


# Beispiel für die Verwendung
```php
function example_custom_adminbar_setup() {
    global $customized_wordpress_adminbar;
    
    $adminbar = $customized_wordpress_adminbar;

    // Hauptmenüpunkt hinzufügen
    $parent_id = $adminbar->add(null, 'Benutzerdefiniertes Menü', '#', 'dashicons-menu');

    // Untermenüpunkte hinzufügen
    $adminbar->add($parent_id, 'Dashboard', admin_url(), 'dashicons-dashboard');
    $adminbar->add($parent_id, 'Profil', admin_url('profile.php'), 'dashicons-admin-users');

    // Modal-Fenster hinzufügen
    $adminbar->addModal($parent_id, 'Hilfe', 'help-modal', 'dashicons-editor-help');
    $adminbar->addModalContent('help-modal', '<h2>Hilfe-Inhalt</h2><p>Hier können Sie Ihren Hilfe-Text einfügen.</p>');

    $adminbar->addModal($parent_id, 'Externe Seite', 'external-modal', 'dashicons-admin-site');
    $adminbar->addModalContent('external-modal', 'https://example.com', 'wide', true);

    // Beitrags- oder seitenspezifische Menüpunkte
    if (is_single() || is_page()) {
        $adminbar->add($parent_id, 'Bearbeiten', get_edit_post_link(), 'dashicons-edit');
        $adminbar->add($parent_id, 'Ansehen', get_permalink(), 'dashicons-visibility');
    }
}

add_action('wp', 'example_custom_adminbar_setup');
```
