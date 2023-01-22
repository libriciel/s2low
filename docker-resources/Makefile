# Ce script est nécessaire pour reconstruire les tables de NAT iptables après le démarrage du container web

# Données récupérées de /etc/ipsec.conf
DGFIP_RIGHT_SUBNET := $(shell sed -n '/^\s*rightsubnet/{s/^.*=//p;}' /etc/ipsec.conf)
DGFIP_LEFT_SOURCE_IP := $(shell sed -n '/^\s*leftsourceip/{s/^.*=//p;}' /etc/ipsec.conf)

# Fixé par Libriciel
DOCKER_SUBNET=10.123.0.0/16

IPTABLES_NAT_RULES=-d ${DGFIP_RIGHT_SUBNET} -s ${DOCKER_SUBNET} ! -o docker0 -j SNAT --to ${DGFIP_LEFT_SOURCE_IP}

DOCKER=docker
DOCKER_COMPOSE=docker compose

.DEFAULT_GOAL := help
.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

up: ## Up all container
	$(DOCKER_COMPOSE) up -d
	make iptables-start

start: ## start
	$(DOCKER_COMPOSE) start
	make iptables-start

down: iptables-stop ## Stop all services
	$(DOCKER_COMPOSE) down

stop: iptables-stop ## Down all services
	$(DOCKER_COMPOSE) stop

iptables-stop:
	if iptables -t nat -C POSTROUTING ${IPTABLES_NAT_RULES} 2>/dev/null; then \
		iptables -t nat -D POSTROUTING ${IPTABLES_NAT_RULES}; \
	fi

iptables-start:
	if ! iptables -t nat -C POSTROUTING ${IPTABLES_NAT_RULES} 2>/dev/null; then \
		iptables -t nat -I POSTROUTING 1 ${IPTABLES_NAT_RULES}; \
	fi
