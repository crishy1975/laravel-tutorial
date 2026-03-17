ssh-befehl import

ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && bash"                                   

ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan up" 

ssh -p 65002 u192633638@212.1.209.26 "cd ~/domains/reschc.space/public_html && bash"

ssh -p 65002 u854179217@212.1.209.26 "cd ~/domains/christianresch.esy.es/public_html/martin"

php artisan optimize:clear

ssh -p 65002 u854179217@212.1.209.26 "cd ~/domains/reschc.space/public_html && php artisan config:clear && php artisan cache:clear"




ssh -p 65002 -t u854179217@212.1.209.26 "cd ~/domains/christianresch.esy.es/public_html/martin && bash"

ssh -t -p 65002 u192633ml && bash -i"