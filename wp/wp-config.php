<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier contient les réglages de configuration suivants : réglages MySQL,
 * préfixe de table, clés secrètes, langue utilisée, et ABSPATH.
 * Vous pouvez en savoir plus à leur sujet en allant sur
 * {@link http://codex.wordpress.org/fr:Modifier_wp-config.php Modifier
 * wp-config.php}. C’est votre hébergeur qui doit vous donner vos
 * codes MySQL.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en "wp-config.php" et remplir les
 * valeurs.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define('DB_NAME', 'colony19_blog');

/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'root');

/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'TjmSKJsb38');

/** Adresse de l’hébergement MySQL. */
define('DB_HOST', 'localhost');

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define('DB_CHARSET', 'utf8mb4');

/** Type de collation de la base de données.
  * N’y touchez que si vous savez ce que vous faites.
  */
define('DB_COLLATE', '');

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clefs secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Tx>w##&~P+y90s<mdgWA[?6IO7rqR&$F~`Z~;Vl56:5zL{f~wk>cgSV./jtfd,!a');
define('SECURE_AUTH_KEY',  '^j66(by3r2xlTGNgr4h;k,kH`5?,R*,dq86yMMA r(dDU/~_=1iMK`ULl~H9<iw-');
define('LOGGED_IN_KEY',    '7Uh%qrCW1>#QqCYeJc.}qP]P?j%HN~+_>bU7Tzz<tgq}b3cpMjEM|4_m?s3{(<He');
define('NONCE_KEY',        '}D!G;7D:t+pASODZM@Fx[6@G2bs7(c^X@Uw9j17{bs!d0`fxV,Q%AV8,),D&iMOC');
define('AUTH_SALT',        '(vM_{rj(P<5(%+HZySlTa|([@eK9bc@LNxK6~XGYZGp:B~Y>ju|`o3&-m$kL&T`J');
define('SECURE_AUTH_SALT', 'jC%jU3NviE@pjR@(P=lyj7z:ZLDZ5ya[<gZ3Q<Ym__%L`{f=1,K3%lN?dr_oaItK');
define('LOGGED_IN_SALT',   '{SPm}uHsGOzHsUaq/0MsZUU~m.8b4T/nL$EBqWLP#f=D8rFzB(,Q#/H5cBAcYV 9');
define('NONCE_SALT',       'G}1D%4g~9I5I4!a<WiQpIeE ~1{4Xo1 `MPK~#mYD@B_.[nVA$efP4F(1+V`fe%D');
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix  = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d'information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* C’est tout, ne touchez pas à ce qui suit ! */

/** Chemin absolu vers le dossier de WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once(ABSPATH . 'wp-settings.php');
