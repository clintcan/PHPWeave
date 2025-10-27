# PHPWeave Kubernetes Deployment

Quick reference for deploying PHPWeave on Kubernetes.

## Prerequisites
- Kubernetes cluster (1.19+)
- kubectl configured
- NGINX Ingress Controller
- Metrics Server (for auto-scaling)

## Quick Deploy

```bash
# 1. Update secrets
cp k8s/secrets.env.example k8s/secrets.env
# Edit k8s/secrets.env with your credentials

# 2. Update domain
# Edit k8s/ingress.yaml - replace phpweave.example.com

# 3. Deploy
kubectl create namespace phpweave
kubectl apply -k k8s/

# 4. Verify
kubectl get pods -n phpweave
kubectl get ingress -n phpweave
```

## Files
- `deployment.yaml` - PHPWeave application (3 replicas)
- `service.yaml` - ClusterIP service
- `configmap.yaml` - Environment configuration
- `secret.yaml` - Database credentials
- `hpa.yaml` - Auto-scaling (3-10 pods)
- `ingress.yaml` - NGINX ingress with SSL
- `pvc.yaml` - Persistent storage
- `mysql.yaml` - MySQL database
- `kustomization.yaml` - Kustomize config
- `health.php` - Health check endpoint

## Important Notes
1. Update image repository in `deployment.yaml`
2. Configure actual passwords in `secret.yaml`
3. Set your domain in `ingress.yaml`
4. Ensure storage class exists (check with `kubectl get storageclass`)

See `docs/KUBERNETES_DEPLOYMENT.md` for complete guide.